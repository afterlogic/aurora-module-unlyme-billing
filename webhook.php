<?php

use Aurora\System\Enums\LogLevel;

require_once \dirname(__file__) . "/../../system/autoload.php";

\Aurora\System\Api::Init();

$module = \Aurora\modules\UnlymeBilling\Module::getInstance();
$secretKey = $module->getConfig('StripeSecretKey', '');

\Stripe\Stripe::setApiKey($secretKey);

$payload = @file_get_contents('php://input');
$event = null;

try {
    $event = \Stripe\Event::constructFrom(
        json_decode($payload, true)
    );
} catch(\Exception $e) {
    \Aurora\System\Api::LogException($e, LogLevel::Full, 'stripe-webhook-');
    http_response_code(400);
    exit();
}

\Aurora\System\Api::LogObject($event, LogLevel::Full, 'stripe-webhook-');

if ($event && $event->type == 'checkout.session.completed') {
    $session = $event->data->object;

    \Aurora\System\Api::LogObject($session, LogLevel::Full, 'stripe-webhook-');

    $tenantId = $session->metadata->TenantId;
    if ($tenantId) {
        $session_details = \Stripe\Checkout\Session::retrieve([
            'id' => $session->id,
            'expand' => ['line_items'],
        ]);

        $line_items = $session_details->line_items->data;
        if (is_array($line_items) && count($line_items) === 1) {
            $quantity = $line_items[0]->quantity;
            $prev = \Aurora\System\Api::skipCheckUserRole(true);
            $result = $module->Decorator()->UpdateBusinessTenantUserSlot($tenantId, $quantity);
            if ($result) {

                if (!$module->Decorator()->GetActiveSubscriptionId($tenantId)) {
                    $result = $module->Decorator()->SetSubscription($tenantId, $session->subscription);
                } else {
                    \Aurora\System\Api::LogObject('Tenant: ' . $tenantId . ' already has an active subscription', LogLevel::Full, 'stripe-webhook-');

                    http_response_code(400);
                }
            }
            $prev = \Aurora\System\Api::skipCheckUserRole(true);
            if ($result) {
                \Aurora\System\Api::LogObject('Successfully updated user slots: ' . $quantity . ' for tenant: ' . $tenantId, LogLevel::Full, 'stripe-webhook-');
                http_response_code(200);
            } else {
                \Aurora\System\Api::LogObject('User slots are not updated for tenant: ' . $tenantId, LogLevel::Full, 'stripe-webhook-');
                http_response_code(400);
            }
        } else {
            \Aurora\System\Api::LogObject('No line items found in session object', LogLevel::Full, 'stripe-webhook-');
            http_response_code(400);
        }
    } else {
        \Aurora\System\Api::Log('TenantId not specified in metadata', LogLevel::Full, 'stripe-webhook-');
        http_response_code(400);
    }
}

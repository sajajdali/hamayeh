<?php

return ['api_token' => env('SHSMS_API_TOKEN'), 'sandbox' => env('SMS_SEND_SANDBOX', false), 'endpoint' => env('SHSMS_ENDPOINT', 'https://shsms.ir/api/v1/sendms'), 'templates' => ['login_webapp' => env('SMS_LOGIN_TEMPLATE_WEBAPP')]];

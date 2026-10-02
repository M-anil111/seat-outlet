<?php
// Placeholder: the full endpoint (reCAPTCHA check, validation, rate limit, storage, welcome email) is built in workstream WS1.
header('Content-Type: application/json; charset=UTF-8');
http_response_code(501);
echo json_encode(['status' => 'error', 'message' => 'Sign-up is not available yet.']);

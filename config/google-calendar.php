<?php

return [

    /*
     * Path to the json file containing the credentials.
     * service-account-credentials
     * idp-bot-6e452c2a3c21
     */
    'service_account_credentials_json' => public_path('/google-calendar/idp-bot-6e452c2a3c21.json'),

    /*
     *  The id of the Google Calendar that will be used by default.
     */
    'calendar_id' => env('GOOGLE_CALENDAR_ID'),
];

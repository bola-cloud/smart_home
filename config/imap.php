<?php
/*
* File:     imap.php
* Category: config
* Author:   M. Goldenbaum
* Created:  24.09.16 22:36
* Updated:  -
*
* Description:
*  -
*/

return [

    /*
    |--------------------------------------------------------------------------
    | IMAP default account
    |--------------------------------------------------------------------------
    |
    | The default account identifier. It will be used as default for any missing account parameters.
    | If however the default account is missing a parameter the package default will be used.
    | Set to 'false' [boolean] to disable this functionality.
    |
    */
    'default' => env('IMAP_DEFAULT_ACCOUNT', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Default date format
    |--------------------------------------------------------------------------
    |
    | The default date format is used to convert any given Carbon::class object into a valid date string.
    | These are currently known working formats: "d-M-Y", "d-M-y", "d M y"
    |
    */
    'date_format' => 'd-M-Y',

    /*
    |--------------------------------------------------------------------------
    | Available IMAP accounts
    |--------------------------------------------------------------------------
    |
    | Please list all IMAP accounts which you are planning to use within the
    | array below.
    |
    */
    'accounts' => [

        'default' => [ // account identifier
            'host'  => env('IMAP_HOST', 'mail.privateemail.com'),  // Your IMAP server
            'port'  => env('IMAP_PORT', 993),                      // IMAP port for SSL
            'protocol'  => env('IMAP_PROTOCOL', 'imap'),           // Protocol, can be 'imap', 'pop3', etc.
            'encryption'    => env('IMAP_ENCRYPTION', 'ssl'),      // SSL encryption for security
            'validate_cert' => env('IMAP_VALIDATE_CERT', true),    // Validate the SSL certificate
            'username' => env('IMAP_USERNAME', 'info@mazaya-aec.com'), // Your email address
            'password' => env('IMAP_PASSWORD', '852456Mm@'),       // Your email password
            'authentication' => env('IMAP_AUTHENTICATION', null),  // Leave this null for normal password authentication
            'proxy' => [
                'socket' => null,
                'request_fulluri' => false,
                'username' => null,
                'password' => null,
            ],
            "timeout" => 30,                                       // Timeout in seconds for the connection
            "extensions" => []
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Available IMAP options
    |--------------------------------------------------------------------------
    |
    | Available php imap config parameters are listed below.
    |
    */
    'options' => [
        'delimiter' => '/',                           // Delimiter for mailbox folders
        'fetch' => \Webklex\PHPIMAP\IMAP::FT_PEEK,     // Do not mark messages as read when fetched
        'sequence' => \Webklex\PHPIMAP\IMAP::ST_UID,   // Use message UID for fetching
        'fetch_body' => true,                          // Fetch the message body
        'fetch_flags' => true,                         // Fetch message flags (e.g., seen, unread)
        'soft_fail' => false,                          // Fail if there are issues fetching messages
        'rfc822' => true,                              // Use RFC822 for email parsing
        'debug' => false,                              // Set to true to enable debug logs
        'uid_cache' => true,                           // Enable UID caching
        'boundary' => '/boundary=(.*?(?=;)|(.*))/i',   // Regular expression for detecting message boundaries
        'message_key' => 'list',                       // How to use keys for messages in arrays
        'fetch_order' => 'asc',                        // Fetch messages in ascending order (oldest first)
        'dispositions' => ['attachment', 'inline'],    // Dispositions for attachments
        'common_folders' => [                          // Default folders
            "root" => "INBOX",
            "junk" => "INBOX/Junk",
            "draft" => "INBOX/Drafts",
            "sent" => "INBOX/Sent",
            "trash" => "INBOX/Trash",
        ],
        'decoder' => [
            'message' => 'utf-8',     // Decoder for message content
            'attachment' => 'utf-8'   // Decoder for attachment names
        ],
        'open' => [
            // 'DISABLE_AUTHENTICATOR' => 'GSSAPI'     // You can disable authenticators if needed for specific servers
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Available flags
    |--------------------------------------------------------------------------
    |
    | List all available / supported flags. Set to null to accept all given flags.
     */
    'flags' => ['recent', 'flagged', 'answered', 'deleted', 'seen', 'draft'],

    /*
    |--------------------------------------------------------------------------
    | Available events
    |--------------------------------------------------------------------------
    |
    */
    'events' => [
        "message" => [
            'new' => \Webklex\IMAP\Events\MessageNewEvent::class,
            'moved' => \Webklex\IMAP\Events\MessageMovedEvent::class,
            'copied' => \Webklex\IMAP\Events\MessageCopiedEvent::class,
            'deleted' => \Webklex\IMAP\Events\MessageDeletedEvent::class,
            'restored' => \Webklex\IMAP\Events\MessageRestoredEvent::class,
        ],
        "folder" => [
            'new' => \Webklex\IMAP\Events\FolderNewEvent::class,
            'moved' => \Webklex\IMAP\Events\FolderMovedEvent::class,
            'deleted' => \Webklex\IMAP\Events\FolderDeletedEvent::class,
        ],
        "flag" => [
            'new' => \Webklex\IMAP\Events\FlagNewEvent::class,
            'deleted' => \Webklex\IMAP\Events\FlagDeletedEvent::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available masking options
    |--------------------------------------------------------------------------
    |
    */
    'masks' => [
        'message' => \Webklex\PHPIMAP\Support\Masks\MessageMask::class,
        'attachment' => \Webklex\PHPIMAP\Support\Masks\AttachmentMask::class
    ]
];

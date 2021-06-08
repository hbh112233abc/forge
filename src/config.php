<?php
return [
    'id'                => 'autodesk forge client id',
    'secret'            => 'autodesk forge secret',
    'prepend_bucketkey' => true,
    'scope_internal'    => ['bucket:create', 'bucket:read', 'data:read', 'data:create', 'data:write'],
    'scope_public'      => ['data:read'],
    'bucket'            => 'default',
];

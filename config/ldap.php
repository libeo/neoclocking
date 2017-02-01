<?php

return [
    'protocol' => env('LDAP_PROTOCOL', 'ldaps'), // ldap or ldaps
    'host' => env('LDAP_HOST', 'ldap.example.com'), // host url
    'port' => env('LDAP_PORT'),
    'rdn' => env('LDAP_RDN', 'dc=example,dc=com'),
    'username_dn' => env('LDAP_USERNAME'),
    'password' => env('LDAP_PASSWORD'),
    'filter' => '(&(objectclass=posixAccount)(|(status=member)))', // optional
    'version' => '3', // LDAP protocol version (2 or 3)
    'login_attribute' => 'uid', // login attributes for users
    'basedn' => 'ou=people,dc=libeo,dc=com', // basedn for users
    'user_id_attribute' => 'uidNumber', // the attribute name containg the uid number
    'user_attributes' => [ // the ldap attributes you want to store in session (ldap_attr => array_field_name)
        'uid' => 'username', // example: this stores the ldap uid attribute as username in GenericUser
    ],
    'use_db' => true, // set to true if you want to retrieve more information from a database,
    // the next 4 variables are required if this is set to true
    'ldap_field' => 'uid', // the LDAP field we want to compare to the db_field to find our user
    'db_table' => 'users', // the table where we should look for users
    'db_field' => 'username', // the DB field we want to compare to the ldap_field to find our user
    'eloquent' => true, // set to true if you want to return an Eloquent user instead of a GenericUser object
    'eloquent_user_model' => NeoClocking\Models\User::class, // name of the User model
];

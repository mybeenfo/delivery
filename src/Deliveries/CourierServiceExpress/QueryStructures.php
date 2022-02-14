<?php

namespace Monastirevrf\DeliveryService\Service\Deliveries\CourierServiceExpress;

trait QueryStructures
{
    protected static function calcStructure(array $structureParams): array
    {
        return [
            'login' => $structureParams['login'],
            'password' => $structureParams['password'],
            'data' => [
                'Key' => 'Destinations',
                'ValueType' => 'string',
                'List' => [
                    'Key' => 'Destinations',
                    'ValueType' => 'string',
                    'Fields' => [
                        [
                            'Key' => 'SenderGeography',
                            'Value' => $structureParams['tradePointGeographyGuid'],
                            'ValueType' => 'string',
                        ],
                        [
                            'Key' => 'RecipientGeography',
                            'Value' => $structureParams['clientGeographyGuid'],
                            'ValueType' => 'string',
                        ],
                        [
                            'Key' => 'TypeOfCargo',
                            'Value' => $structureParams['typeOfCargoGuid'],
                            'ValueType' => 'string',
                        ],
                        [
                            'Key' => 'Weight',
                            'Value' => $structureParams['weight'],
                            'ValueType' => 'float',
                        ],
                        [
                            'Key' => 'Qty',
                            'Value' => $structureParams['packagesCount'],
                            'ValueType' => 'int',
                        ],
                        [
                            'Key' => 'Volume',
                            'Value' => $structureParams['volume'],
                            'ValueType' => 'float',
                        ],
                        [
                            'Key' => 'VolumeWeight',
                            'Value' => 1,
                            'ValueType' => 'float',
                        ],
                        [
                            'Key' => 'Service',
                            'Value' => $structureParams['serviceGuid'],
                            'ValueType' => 'string',
                        ],
                        [
                            'Key' => 'Urgency',
                            'Value' => $structureParams['urgencyGuid'],
                            'ValueType' => 'string',
                        ],
                        [
                            'Key' => 'DeliveryType',
                            'Value' => $structureParams['deliveryTypeValue'],
                            'ValueType' => 'decimal',
                        ],
                    ],
                ]
            ],
            'parameters' => [
                'Key' => 'Parameters',
                'ValueType' => 'string',
                'List' => [
                    [
                        'Key' => 'ipaddress',
                        'Value' => '10.0.0.1',
                        'ValueType' => 'string',
                    ],
                ],
            ],
        ];
    }

    protected static function guidStructure(string $login, string $password, string $postcode): array
    {
        return [
            'login' => $login,
            'password' => $password,
            'parameters' => [
                'Key' => 'Parameters',
                'ValueType' => 'string',
                'List' => [
                    [
                        'Key' => 'Reference',
                        'Value' => 'Geography',
                        'ValueType' => 'string',
                    ],
                    [
                        'Key' => 'Search',
                        'Value' => 'postcode-' . $postcode,
                        'ValueType' => 'string',
                    ],
                ],
            ],
        ];
    }

    protected static function typeOfCargo(string $login, string $password): array
    {
        return [
            'login' => $login,
            'password' => $password,
            'parameters' => [
                'Key' => 'Parameters',
                'ValueType' => 'string',
                'List' => [
                    'Key' => 'Reference',
                    'Value' => 'TypesOfCargo',
                    'ValueType' => 'string',
                ],
            ],
        ];
    }

    protected static function services(string $login, string $password): array
    {
        return [
            'login' => $login,
            'password' => $password,
            'parameters' => [
                'Key' => 'Parameters',
                'ValueType' => 'string',
                'List' => [
                    'Key' => 'Reference',
                    'Value' => 'Services',
                    'ValueType' => 'string',
                ],
            ],
        ];
    }

    protected static function urgency(string $login, string $password): array
    {
        return [
            'login' => $login,
            'password' => $password,
            'parameters' => [
                'Key' => 'Parameters',
                'ValueType' => 'string',
                'List' => [
                    'Key' => 'Reference',
                    'Value' => 'Urgencies',
                    'ValueType' => 'string',
                ],
            ],
        ];
    }

    protected static function deliveryType(string $login, string $password): array
    {
        return [
            'login' => $login,
            'password' => $password,
            'parameters' => [
                'Key' => 'Parameters',
                'ValueType' => 'string',
                'List' => [
                    'Key' => 'Reference',
                    'Value' => 'DeliveryType',
                    'ValueType' => 'string',
                ],
            ],
        ];
    }
}
<?php

$oas = json_decode(file_get_contents('oas.json'), true);
$endpoints = json_decode(file_get_contents('endpoints.json'), true);

$operationIds = [];

$paths = [];
foreach ($endpoints as $endpoint) {
	$endpoint['endpoint'] = trim($endpoint['endpoint']);
	$pathName = str_replace(['<', '>'], ['{', '}'], $endpoint['endpoint']);

	if (!isset($paths[$pathName]))
		$paths[$pathName] = [];

	$newEndpoint = [
		'tags' => [],
		'summary' => $endpoint['endpoint'],
		'description' => $endpoint['description'],
		'operationId' => trim(str_replace('/', '-', $pathName), '-'),
		'parameters' => [],
		'deprecated' => false,
		'security' => [],
		'responses' => [
			'200' => [
				'description' => 'Null response'
			]
		]
	];

	if (in_array($newEndpoint['operationId'], $operationIds))
		$newEndpoint['operationId'] = strtolower($endpoint['method']) . '-' . $newEndpoint['operationId'];

	$operationIds[] = $newEndpoint['operationId'];


	$parameters = [];

	foreach ($endpoint['parameters'] as $parameter) {
		$newParameter = [
			'name' => trim(str_replace('(optional)', '', $parameter['name'])),
			'in' => ($endpoint['method'] == 'POST' ? 'body' : 'query'),
			'description' => rtrim($parameter['description'], '.'),
			'required' => (strpos($parameter['name'], '(optional)') === false),
			'schema' => [
				'type' => 'string',
				'default' => ''
			]
		];

		if ($newParameter['name'] == 'app')
			$newParameter['schema'] = [
				'type' => 'integer',
				'format' => 'int32',
				'default' => 3
			];

		$parameters[] = $newParameter;
	}

	$newEndpoint['parameters'] = $parameters;

	$paths[$pathName][strtolower($endpoint['method'])] = $newEndpoint;
}

$oas['paths'] = $paths;

file_put_contents('output.json', json_encode($oas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
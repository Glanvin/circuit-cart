<?php
$config['error_codes'] = array(
	//P = Page, record, resource
	'P01' => 'No records of {id}',
	'P02' => 'No tech part exists with ID {id}.',
	'P03' => 'You do not have permission to access this page',

	//Q = Query, database failure
	'Q01' => 'Failed to fetch parts.',
	'Q02' => 'Failed to fetch part with ID {id}.',
	'Q03' => 'Failed to get user',

	//D = Data, validation
	'D01' => 'Data is empty',

	//S = Session
	'S01' => 'The Stored Session is invalid. Please login again',
);

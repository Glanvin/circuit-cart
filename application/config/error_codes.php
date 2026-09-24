<?php
$config['error_codes'] = array(
	//P = Page, record, resource
	'P01' => 'No records of {id}',
	'P02' => 'No tech part exists with ID {id}.',
	'P03' => 'You do not have permission to access this page',
	'P04' => 'No booking exists with ID {id}.',

	'P05' => 'This booking has been closed.',

	'P06' => 'No bug report exists with ID {id}.',

	//Q = Query, database failure
	'Q01' => 'Failed to fetch parts.',
	'Q02' => 'Failed to fetch part with ID {id}.',
	'Q03' => 'Failed to get user',
	'Q04' => 'Failed to get booking',
	'Q05' => 'Failed to save bug report.',
	'Q06' => 'Failed to get bug reports.',

	//D = Data, validation
	'D01' => 'Data is empty',

	//S = Session
	'S01' => 'The Stored Session is invalid. Please login again',
);

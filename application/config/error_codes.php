<?php
$config['error_codes'] = array(
	//P = Page, record, resource
	'P01' => 'No records of {id}',
	'P02' => 'No tech part exists with ID {id}.',
	'P03' => 'You do not have permission to access this page',
	'P04' => 'No booking exists with ID {id}.',

	'P05' => 'This booking has been closed.',

	'P06' => 'No bug report exists with ID {id}.',

	'P07' => 'No employee exists with ID {id}.',
	'P08' => 'No sale exists with ID {id}.',

	//Q = Query, database failure
	'Q01' => 'Failed to fetch parts.',
	'Q02' => 'Failed to fetch part with ID {id}.',
	'Q03' => 'Failed to get user',
	'Q04' => 'Failed to get booking',
	'Q05' => 'Failed to save bug report.',
	'Q06' => 'Failed to get bug reports.',
	'Q07' => 'Failed to get employees.',
	'Q08' => 'Failed to save the sale.',
	'Q09' => 'Failed to get sales.',

	//D = Data, validation
	'D01' => 'Data is empty',
	'D02' => 'Insufficient stock for {item}.',
	'D03' => 'Employee {id} is already registered.',

	//S = Session
	'S01' => 'The Stored Session is invalid. Please login again',
);

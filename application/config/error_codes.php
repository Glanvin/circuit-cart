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
	'P09' => 'No repair job exists with ID {id}.',

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
	'Q10' => 'Failed to get appointment slots.',
	'Q11' => 'Failed to get repair job.',
	'Q12' => 'Failed to get tax rates.',
	'Q13' => 'Failed to get payment methods.',
	'Q14' => 'Failed to get stock movements.',

	//U = User spreadsheet import
	'U01' => 'The spreadsheet could not be read. Download the sample file and check its contents.',
	'U02' => 'The spreadsheet is missing required columns: {columns}',
	'U03' => 'The spreadsheet has no user rows to import.',
	'U04' => 'The spreadsheet has more than {max} rows. Split it into smaller files.',
	'U05' => 'No users could be imported. Fix the rows listed below and upload the file again.',

	//D = Data, validation
	'D01' => 'Data is empty',
	'D02' => 'Insufficient stock for {item}.',
	'D03' => 'Employee {id} is already registered.',
	'D04' => 'No estimate to approve.',
	'D05' => 'Payment total does not match sale total.',

	//S = Session
	'S01' => 'The Stored Session is invalid. Please login again',
);

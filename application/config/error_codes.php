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
	'P10' => 'No repair job exists with that reference.',
	'P11' => 'No promo code exists with ID {id}.',
	'P12' => 'No repair service exists with ID {id}.',

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
	'Q15' => 'Failed to reverse the sale.',
	'Q16' => 'Failed to process promo codes.',
	'Q17' => 'Failed to process the wishlist.',
	'Q18' => 'Failed to process product reviews.',
	'Q19' => 'Failed to process repair services.',
	'Q20' => 'Failed to process repair payments.',

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
	'D06' => 'This sale has already been finalised and cannot be changed.',
	'D07' => 'Only store sales can be refunded or voided.',
	'D08' => 'Only online orders can be cancelled.',
	'D09' => 'This order can no longer be cancelled.',
	'D10' => 'This estimate link has expired. Please contact the shop.',
	'D11' => 'Refunds over ₱{limit} require a manager. Ask a manager to process this refund.',
	'D12' => 'This repair job is closed and can no longer be changed.',
	'D13' => 'A repair job cannot move from {from} to {to}.',
	'D14' => 'This booking cannot be changed at its current stage.',
	'D15' => 'Promo code {code} is not valid.',
	'D16' => 'Promo code {code} has expired.',
	'D17' => 'Promo code {code} requires a minimum subtotal of ₱{min}.',
	'D18' => 'Promo code {code} has reached its usage limit.',
	'D19' => 'Rating must be between 1 and 5.',
	'D20' => 'That review status is not valid.',
	'D21' => 'Payment amount must be greater than zero.',
	'D22' => 'This repair job has an outstanding balance of ₱{balance}. Collect the balance before releasing the device.',
	'D23' => 'The selected payment method is not available.',
	'D24' => 'This booking can no longer be cancelled or rescheduled online. Please contact the shop.',
	'D25' => 'The selected time slot is not available or has reached capacity.',

	//S = Session
	'S01' => 'The Stored Session is invalid. Please login again',
);

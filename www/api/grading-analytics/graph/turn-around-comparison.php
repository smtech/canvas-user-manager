<?php

require_once('../config.inc.php');
require_once('../common.inc.php');
require_once('../.ignore.grading-analytics-authentication.inc.php');
require_once('../../mysql.inc.php');
require_once('../../phpgraphlib.php');

$stats = mysqlQuery("
	SELECT * FROM (
		SELECT * FROM `course_statistics`
			WHERE
				`average_grading_turn_around` > 0" .
				(
					isset($_REQUEST['department_id']) ? "
					AND `course[account_id]` = '{$_REQUEST['department_id']}'
					" :
					''
				) . "
			ORDER BY
				`timestamp` DESC
	) AS `stats`
		GROUP BY
		`course[id]`
");

$data = array();
$total = 0;
$divisor = 0;
while ($row = $stats->fetch_assoc()) {
	$data[$row['course[id]']] = $row['average_grading_turn_around'];
	$total += $row['average_grading_turn_around'] * $row['student_count'] * $row['graded_assignment_count'];
	$divisor += $row['student_count'] * $row['graded_assignment_count'];
}
asort($data);
$highlight = $data;
$data[$_REQUEST['course_id']] = 0;
while (list($key, $value) = each ($highlight)) {
	if ($key != $_REQUEST['course_id']) {
		$highlight[$key] = 0;
	}
}

$graph = new PHPGraphLib(graphWidth(count($data)), graphHeight());
$graph->addData($data);
$graph->addData($highlight);
$graph->setBarColor('gray', 'red');
$graph->setBarOutline(false);
$graph->setGoalLine($total / $divisor, 'gray', 'dashed');
$graph->setGoalLine(7, 'lime', 'solid');
$graph->setGoalLine(14, 'red', 'solid');
$graph->setGrid(false);
$graph->setXValues(false);
$graph->createGraph();

?>
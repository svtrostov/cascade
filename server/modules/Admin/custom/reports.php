<?php
/*==================================================================================================
Описание: Генерация отчетов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


$report_type	= strtolower(trim($request->getStr('report_type', '')));
$report_format	= strtolower(trim($request->getStr('report_format', 'pdf')));

if(!in_array($report_format,array('pdf'))) $report_format = 'pdf';




/***********************************************************************
 * ОСНОВНАЯ ЧАСТЬ
 **********************************************************************/
$report_data = array(
	'type'			=> $report_type,
	'format'		=> $report_format,
	'employer_id'	=> $user->getEmployerID()
);

#Определение типа запрашиваемого отчета
switch($report_type){

	#Печатная форма заявки
	case 'request':
		$report_data['request_id'] = $request->getId('request_id',0);
		$report_data['iresource_id'] = $request->getId('iresource_id',0);
		if(empty($report_data['request_id'])||empty($report_data['iresource_id'])) die('Incorrect request_id/iresource_id param');
		goto LABEL_REPORT_REQUEST;
	break;

	
	
	
	

	default: 
		die('Undefined report type');

}#Определение типа запрашиваемого отчета

exit;




/***********************************************************************
 * ФУНКЦИИ
 **********************************************************************/











/***********************************************************************
 * ОБРАБОТЧИКИ
 **********************************************************************/



#Печатная форма заявки
LABEL_REPORT_REQUEST:


	$main_request = new Main_Request();
	if(!$main_request->dbIsEmployerRequest($report_data['employer_id'], $report_data['request_id'])){
		die('You are not allowed to view this content');
	}


	$report_data['request_info'] = $main_request->dbGetInfo($report_data['request_id'],true);
	if(empty($report_data['request_info'])) die('Not found: request_info, request_id='.$report_data['request_id']);
	$report_data['iresource_info'] = $main_request->dbGetIResource($report_data['request_id'], $report_data['iresource_id'], true);
	if(empty($report_data['iresource_info'])) die('Not found: iresource_info, request_id='.$report_data['request_id'].', iresource_id='.$report_data['iresource_id']);

	$main_request->cache['iresources'][$report_data['iresource_id']] = $report_data['iresource_info'];
	$iroles_raw = $main_request->dbGetAllIRoles($report_data['request_id'], $report_data['iresource_id'], $report_data['request_info']['employer_id']);
	$ir_types = Database::getInstance('main')->selectByKey('item_id','SELECT * FROM `ir_types`');
	$iroles = array();
	$count = count($iroles_raw);
	for($i=0;$i<$count;$i++){
		if(!is_array($iroles_raw[$i])){
			$iroles[] = $iroles_raw[$i];
			continue;
		}
		if($iroles_raw[$i]['ir_selected'] == 0) continue;
		$iroles_raw[$i]['ir_selected_name'] = (empty($ir_types[$iroles_raw[$i]['ir_selected']]) ? '?:id='.$iroles_raw[$i]['ir_selected'] : $ir_types[$iroles_raw[$i]['ir_selected']]['full_name']);
		$iroles[] = $iroles_raw[$i];
	}

	$main_employer = new Main_Employer();
	$agreement = '';
	$statement = '';

	$steps = $main_request->dbGetRequestStepsFullInfo($report_data['request_id'], $report_data['iresource_id']);
	if(!empty($steps)&&is_array($steps)){
		foreach($steps as $step){
			if($step['step_type']==2 && $step['step_complete']==1 && ($step['gatekeeper_id']>0 ||$step['assistant_id']>0)){
				if($step['gatekeeper_role']=='1' && empty($agreement)){
					$agreement = $main_employer->getEmployerName(($step['gatekeeper_id']>0 ? $step['gatekeeper_id'] : $step['assistant_id']),'{last} {f}.{m}.');
					if($step['gatekeeper_id']==0) $agreement ='/'.$agreement.'/';
				}else
				if($step['gatekeeper_role']=='2' && empty($statement)){
					$statement = $main_employer->getEmployerName(($step['gatekeeper_id']>0 ? $step['gatekeeper_id'] : $step['assistant_id']),'{last} {f}.{m}.');
					if($step['gatekeeper_id']==0) $statement ='/'.$statement.'/';
				}
			}
		}
	}
	$report_data['request_info']['agreement_employer'] = (empty($agreement) ? '(подпись, Ф.И.О.)' : $agreement);
	$report_data['request_info']['statement_employer'] = (empty($statement) ? '(подпись, Ф.И.О.)' : $statement);
/*
	echo "<pre>";
	print_r('statement_employer'.$report_data['request_info']['statement_employer']);
	print_r($steps);
	echo "</pre>";
	exit;
*/

	$report_data['report_time'] = date("d.m.Y H:i:s");
	$report_data['report_user'] = $main_employer->getEmployerName($report_data['employer_id'],'{last} {f}.{m}.');
	$report_data['request_info']['employer_fio'] = $main_employer->getEmployerName($report_data['request_info']['employer_name'],'{last} {f}.{m}.');


	#Рендеринг заявки в PDF
	$pdf = new Main_PDFReportRequest($report_data);



	$n_pp = 0;
	$fill = true;
	$pdf->SetFillStatus(false);
	$column = 0;
	$count = count($iroles);

	#Построение таблицы запрошенных доступов
	foreach($iroles as $indx=>$irole){

		$is_area = (!is_array($irole) ? true : false);

		if($pdf->GetY()+($is_area?25:20) > $pdf->PageBreakTrigger || $indx==0){
			if($indx!=0) $pdf->AddPage($pdf->CurOrientation);
			$pdf->SetFillStatus(false);
			$pdf->SetFont('Arial','B',9);
			$pdf->SetWidths(array(10,70,80,30));
			$pdf->SetAligns(array('C','C','C','C'));
			$pdf->Row(array('№ п/п','Название / функционал','Описание','Тип доступа'));
			$pdf->SetFont('Arial','',8);
			$pdf->SetFillColor(240,240,240);
			$pdf->SetAligns(array('C','L','L','C'));
		}

		#Новый раздел
		if($is_area){
			if($indx>=$count-1) continue;
			if(!is_array($iroles[$indx+1])) continue;
			$x=$pdf->GetX();
			$y=$pdf->GetY();
			if($y+15 > $pdf->PageBreakTrigger){
				$pdf->AddPage($pdf->CurOrientation);
				$x=$pdf->GetX();
				$y=$pdf->GetY();
			}
			$pdf->SetFont('Arial','B',8);
			$pdf->SetFillColor(210,210,210);
			$pdf->SetFillStatus(true);
			$pdf->Rect($x,$y,190,7, ('DF'));
			$pdf->MultiCell(190,7,$irole,0,'L');
			$pdf->SetFont('Arial','',8);
			$pdf->SetFillColor(240,240,240);
			$pdf->SetXY($x, $y+7);
			continue;
		}

		$n_pp++;
		$pdf->SetFillStatus($fill);
		$pdf->Row(array($n_pp, trim($irole['full_name']),trim($irole['description']), $irole['ir_selected_name']));
		$fill = !$fill;

	}#Построение таблицы запрошенных доступов
/*
	$btable_y = $pdf->GetY();
	$n_pp = 0;
	$fill = true;
	$pdf->SetFillStatus(false);
	$column = 0;
	$count = count($iroles);
	#Построение таблицы запрошенных доступов
	foreach($iroles as $indx=>$irole){

		$is_area = (!is_array($irole) ? true : false);

		if($pdf->GetY()+($is_area?15:10) > $pdf->PageBreakTrigger || $indx==0){
			if($column==0 && $indx!=0){
				$column++;
				$pdf->SetXY(105, $btable_y);
			}else{
				$column=0;
				if($indx!=0) $pdf->AddPage($pdf->CurOrientation);
				$btable_y = $pdf->GetY();
			}
			$pdf->SetFillStatus(false);
			$pdf->SetFont('Arial','B',9);
			$pdf->SetWidths(array(10,55,25));
			$pdf->SetAligns(array('C','C','C'));
			$pdf->Row(array('№ п/п','Название / функционал','Тип доступа'));
			$pdf->SetFont('Arial','',8);
			$pdf->SetFillColor(240,240,240);
			$pdf->SetAligns(array('C','L','C'));
		}

		#Новый раздел
		if($is_area){
			if($indx>=$count-1) continue;
			if(!is_array($iroles[$indx+1])) continue;
			$x=$pdf->GetX();
			$y=$pdf->GetY();
			$pdf->SetFont('Arial','B',8);
			$pdf->SetFillColor(210,210,210);
			$pdf->SetFillStatus(true);
			$pdf->Rect($x,$y,90,8, ('DF'));
			$pdf->MultiCell(90,8,$irole,0,'L');
			$pdf->SetFont('Arial','',8);
			$pdf->SetFillColor(240,240,240);
			$pdf->SetXY($x, $y+8);
			continue;
		}

		$n_pp++;
		$pdf->SetFillStatus($fill);
		$pdf->Row(array($n_pp, trim($irole['full_name']), $irole['ir_selected_name']));
		$fill = !$fill;
	}#Построение таблицы запрошенных доступов
*/

	$pdf->Output('rreport_'.$report_data['request_id'].'-'.$report_data['iresource_id'].'.pdf','D');

exit;



?>
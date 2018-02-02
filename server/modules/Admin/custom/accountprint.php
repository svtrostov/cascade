<?php
/*==================================================================================================
Описание: Генерация отчетов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');
if(!UserAccess::_checkAccess('employers.info.change')) die('You are not allowed to view this content');

$report_format = $request->getEnum('format',array('pdf','docx'),'pdf');
$print_info = $request->getBool('info',false);
$print_password = $request->getBool('password',false);
$print_pincode = $request->getBool('pincode',false);
$employer_id = $request->getId('employer_id',false);

if(empty($employer_id)) die('Incorrect request');
if(!$print_info && !$print_password && !$print_pincode) die('Empty request - empty response :)');



/***********************************************************************
 * ФУНКЦИИ
 **********************************************************************/


//Добавить строку ключ: значение
function addAttrLine($pdf, $key, $value, $height=4, $key_width=45, $value_width=150){
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell($key_width,$height,$key.':',0,'LT');
	$pdf->SetFont('Arial','',10);
	$pdf->MultiCell($value_width,$height, $value,0,'LT');
}#end function


/***********************************************************************
 * ОСНОВНАЯ ЧАСТЬ
 **********************************************************************/

$admin_employers = new Admin_Employers();
$employer_info = $admin_employers->getEmployersList($employer_id, null, true, false);
if(empty($employer_info)) die('Employer ID:'.$employer_id.' not found.');

$employer_info['password'] = (strlen($employer_info['password']) == 40 ? '-[Пароль закрыт]-' : $employer_info['password']);
$employer_info['pin_code'] = (strlen($employer_info['pin_code']) == 40 ? '-[PIN закрыт]-' : $employer_info['pin_code']);

if($report_format == 'pdf') goto LABEL_PDF;

$template_file = DIR_MODULES.'/Admin/templates/docx/pin.docx';

$docx = new phpDocx($template_file);

foreach($employer_info as $key => $value){
	$docx->assign('${'.$key.'}',$value);
}
$docx->save('pin_account_'.$employer_id.'.docx');
$docx->download('pin_account_'.$employer_id.'.docx');
exit;




LABEL_PDF:

#Рендеринг в PDF
$pdf = new FPDF();

$pdf->FPDF('P','mm','A4');
$pdf->AddFont('Arial','','tahoma.php');
$pdf->AddFont('Arial','B','tahoma_bold.php');
$pdf->SetAuthor('FP Cascade', true);
$pdf->SetCreator('FP Cascade', true);
$pdf->SetProducer('FP Cascade', true);
$pdf->SetTitle('Учетные данные сотрудника', true);
$pdf->SetSubject('Данная информация является конфиденциальной', true);
$pdf->SetFont('Arial','',10);
$pdf->SetLineWidth(0.1);
$pdf->AddPage();
$pdf->fill_status=false;


#Построение страницы
$start_x = $pdf->GetX();
$start_y = $pdf->GetY();

$y = $start_y;
$pdf->SetXY(150, $y);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(50, 4, '"КОНФИДЕНЦИАЛЬНО"', 0, 1, 'R');
$pdf->SetXY($start_x, $start_y);

#Создание заголовка
$pdf->SetFont('Arial','B',16);
$pdf->Cell(140, 4,'Учетные данные сотрудника',0,0,'LT');

$pdf->SetXY($start_x, 26);


#Требуется вывод информации о сотруднике
if($print_info){
	$fields_title = array(
		'employer_id' 	=> 'Идентификатор',
		'search_name' 	=> 'ФИО сотрудника',
		'birth_date' 	=> 'Дата рождения',
		'phone' 		=> 'Контактный телефон',
		'email' 		=> 'Электронная почта',
		'username' 		=> 'Имя пользователя'
	);
	$fields_show = array('employer_id','username','search_name','birth_date','phone','email');
	foreach($fields_show as $field){
		addAttrLine($pdf, $fields_title[$field], $employer_info[$field]);
		$pdf->Ln(4);
	}
	$pdf->Ln(10);
}#Требуется вывод информации о сотруднике



#Требуется вывод пароля сотрудника
if($print_password){
		addAttrLine($pdf, 'Пароль', $employer_info['password']);
		$pdf->Ln(14);
}#Требуется вывод пароля сотрудника



#Требуется вывод PIN-кода
if($print_pincode){
		addAttrLine($pdf, 'PIN-код', $employer_info['pin_code']);
		$pdf->Ln(14);
}#Требуется вывод PIN-кода


$pdf->Output('account_'.$employer_id.'.pdf','D');

exit;
?>
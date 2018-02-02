<?php
/*==================================================================================================
Описание: Рендеринг отчетов в PDF формате
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');

require_once(DIR_CLASSES.'/FPDF.class.php');

class Main_PDFReportPerformer extends FPDF{

	var $widths;
	var $aligns;
	var $fill_status=false;
	var $row_lines;
	var $report;


	public function __construct($report){
		$this->FPDF('P','mm','A4');
		$this->AddFont('Arial','','tahoma.php');
		$this->AddFont('Arial','B','tahoma_bold.php');
		$this->SetAuthor('FP Cascade', true);
		$this->SetCreator('FP Cascade', true);
		$this->SetProducer('FP Cascade', true);
		$this->SetTitle('Заявка на доступ к информационному ресурсу', true);
		$this->SetSubject('Заявка на доступ к информационному ресурсу', true);
		$this->report = $report;
		$this->SetFont('Arial','',10);
		$this->SetLineWidth(0.1);
		$this->AddPage();

	}#end function



	//Верхний колонтитул
	function Header(){

		if($this->page == 1){

			$code = $this->report['request_id'].'-'.
					$this->report['iresource_id'].'-'.
					$this->report['request_info']['employer_id'].'-'.
					$this->report['request_info']['company_id'].'-'.
					$this->report['request_info']['post_uid'].'-'.
					User::_getEmployerId();

			$start_x = $this->GetX();
			$start_y = $this->GetY();
			$y = $start_y;
			$this->SetXY(140, $y);
			$this->SetFont('Arial','B',10);
			$this->Cell(50, 4, '"ДЛЯ СЛУЖЕБНОГО ПОЛЬЗОВАНИЯ"', 0, 1, 'C');
			$this->SetFont('Arial','',10);
			$this->SetXY(140,$y+6);
			$this->Cell(50, 4, 'специалистами IT подразделений', 0, 1, 'C');
			$this->SetXY(140,$y+10);
			$this->Cell(50, 4, 'в процессе исполнения заявки', 0, 1, 'C');
			$this->SetXY(140,$y+18);
			$this->SetFont('Arial','',8);
			$this->Cell(50, 4, '['.$code.']', 0, 1, 'C');

			$this->SetXY($start_x, $start_y);

			#Создание заголовка
			$this->SetFont('Arial','B',14);
			$this->Cell(140, 4,'Заявка №'.$this->report['request_id'].' от '.$this->report['request_info']['create_date'],0,0,'LT');

			$this->SetY(16);
			$this->SetFont('Arial','B',11);
			$this->addTextLine('на '.($this->report['request_info']['request_type']==3?'блокировку':'открытие').' доступа к информационному ресурсу:',5,120,'L');
			$this->Ln(4);
			$this->SetFont('Arial','',11);
			$this->addTextLine($this->report['iresource_info']['iresource_name'],5,120,'L');

			$this->SetY($this->GetY()+4);

			$this->SetFont('Arial','B',10);
			$this->addAttrLine('Заявитель:',$this->report['request_info']['employer_name'], 5);
			$this->SetFont('Arial','',9);
			$this->addAttrLine('Идентификатор:',$this->report['request_info']['employer_id'], 4);
			$this->addAttrLine('Имя пользователя:',$this->report['request_info']['employer_username'], 4);
			$this->addAttrLine('Органзиция:',$this->report['request_info']['company_name'], 4);
			$this->addAttrLine('Должность:',$this->report['request_info']['post_name'], 4);
			if(!empty($this->report['request_info']['phone'])) $this->addAttrLine('Телефон:',$this->report['request_info']['phone'], 4);
			if(!empty($this->report['request_info']['email'])) $this->addAttrLine('E-mail:',$this->report['request_info']['email'], 4);

			
			$this->Ln(6);
			$this->SetFont('Arial','',11);
			$this->addTextLine('Прошу '.($this->report['request_info']['request_type']==3?'блокировать':'предоставить').' доступ к следующему функционалу:');
			$this->Ln(2);
			/*
			$this->SetFont('Arial','B',12);
			$this->Cell(190,6,'за период с '.$this->order_from.' по '.$this->order_to,0,1,'L');
			$this->SetFont('Arial','',10);
			$this->Cell(190,6,$this->order_client_name.' ('.$this->order_client_id.')',0,1,'L');
			* */
			return;
		}

		if($this->CurOrientation == 'P'){
			$this->SetFont('Arial','',8);
			$this->Cell(190, 4, 'Заявка №'.$this->report['request_id'].' от '.$this->report['request_info']['create_date'].': '.$this->report['iresource_info']['iresource_name'], 0, 1, 'L');
			$this->Cell(130, 4, $this->report['request_info']['employer_name'].', '.$this->report['request_info']['company_name'], 0, 0, 'L');
			$this->Cell(60, 3, 'стр. '.$this->page, 0, 0, 'R');
			$this->SetLineWidth(0.1);
			$this->Line(10,20,200,20);
			$this->Ln(10);
		}else{
			$this->SetFont('Arial','',8);
			$this->Cell(280, 4, 'Заявка на доступ: '.$this->report['iresource_info']['iresource_name'], 0, 1, 'L');
			$this->Cell(230, 4, $this->report['request_info']['employer_name'].', '.$this->report['request_info']['company_name'], 0, 0, 'L');
			$this->Cell(50, 4, 'стр. '.$this->page, 0, 0, 'R');
			$this->SetLineWidth(0.1);
			$this->Line(10,20,290,20);
			$this->Ln(10);
		}

	}#end function



	function Footer(){

	}#end function



	//Добавить строку ключ: значение
	public function addAttrLine($key, $value, $height=4, $key_width=35, $value_width=150){
		$this->Cell($key_width,$height,$key,0,'LT');
		$this->MultiCell($value_width,$height, $value,0,'LT');
	}#end function


	//Добавить строку
	public function addTextLine($value, $height=8, $value_width=180,$align='L'){
		$this->MultiCell($value_width, $height, $value,0,$align);
	}#end function





	public function SetWidths($w){
		$this->widths=$w;
	}

	public function SetAligns($a){
		$this->aligns=$a;
	}

	public function SetFillStatus($a){
		$this->fill_status=$a;
	}

	private function drawCell($i, $data, $h){

		$w=$this->widths[$i];
		$a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
		$x=$this->GetX();
		$y=$this->GetY();
		$this->Rect($x,$y,$w,$h, ($this->fill_status? 'DF':'D'));

		if(is_array($data)){
			$indx=0;
			foreach($data as $item){
				$ich = $h / count($data);
				$this->SetXY($x,$y+$indx*$ich);
				$this->Cell($w,$ich,$item, ($indx > 0 ? 'T':0),1,$a);//$this->Cell($w,5,$item,0,$a);
				$indx++;
			}
		}else{
			$ich = $h / $this->row_lines[$i];
			$this->MultiCell($w,$ich,$data,0,$a);
		}

		$this->SetXY($x+$w,$y);

	}

	public function Row($data){
		$x=$this->GetX();
		$nb=0;
		$this->row_lines = array();
		for($i=0;$i<count($data);$i++){
			$data[$i] = $this->_textEncode($data[$i]);
			$this->row_lines[$i] = $this->NbLines($this->widths[$i],(is_array($data[$i])?implode("\n",$data[$i]):$data[$i]));
			$nb=max($nb, $this->row_lines[$i]);
		}
		$h=4*$nb+1;
		$this->CheckPageBreak($h);
		for($i=0;$i<count($data);$i++){
			$this->drawCell($i, $data[$i], $h);
		}
		//$this->Ln($h);
		$this->SetXY($x, $this->GetY()+$h);
	}

	function CheckPageBreak($h){
		if($this->GetY()+$h>$this->PageBreakTrigger) $this->AddPage($this->CurOrientation);
	}

	public function NbLines($w,$txt){
		$cw=&$this->CurrentFont['cw'];
		if($w==0) $w=$this->w-$this->rMargin-$this->x;
		$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
		$s=str_replace("\r",'',$txt);
		$nb=strlen($s);
		if($nb>0 and $s[$nb-1]=="\n") $nb--;
		$sep=-1;
		$i=0;
		$j=0;
		$l=0;
		$nl=1;
		while($i<$nb){
			$c=$s[$i];
			if($c=="\n"){
				$i++;
				$sep=-1;
				$j=$i;
				$l=0;
				$nl++;
				continue;
			}
			if($c==' ') $sep=$i;
			$l+=$cw[$c];
			if($l>$wmax) {
				if($sep==-1){
					if($i==$j) $i++;
				}else{
					$i=$sep+1;
				}
				$sep=-1;
				$j=$i;
				$l=0;
				$nl++;
			}
			else{
				$i++;
			}
		}
		return $nl;
	}




}#end class
?>
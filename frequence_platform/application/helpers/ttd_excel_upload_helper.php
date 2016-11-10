<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function export_sites($site_array, $title, $id)
{
	if (count($site_array)>0)
	{
		$objPHPExcel=new PHPExcel();
		
		$objPHPExcel->getProperties()->setCreator("Vantage Local")
		->setLastModifiedBy("Vantage Local")
		->setTitle("Site Data for Proposal ". $id ."")
		->setSubject("Site Data for Proposal ". $id ."")
		->setDescription("Site Data");
		
		$cell_counter=2;
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'Domain')->setCellValue('B1', 'Category ID')->setCellValue('C1', 'Adjustment')->setCellValue('E1', 'Category Name (Reference Only)');
		foreach ($site_array as $v)
		{
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$cell_counter.'', $v)->setCellValue('C'.$cell_counter.'', "1");
			$cell_counter++;
		}
		$objPHPExcel->getActiveSheet()->setTitle("Template");
			
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="Sites_'. $title .'_'. date('dmy_Hi') .'.xlsx"');
		header('Cache-Control: max-age=0');
			
		$objWriter=PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
	}
	else
	{
		echo "No sites found";
	}
}
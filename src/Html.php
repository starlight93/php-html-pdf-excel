<?php

namespace Starlight93\HtmlPdfExcel;

use MathParser\StdMathParser;
use MathParser\Interpreting\Evaluator;

class Html {
    public $data;
    public $templateArr;
    public $fontSize; 
    public $break;
    public $isMulti = false;
    public $config = [
        'fontSize' => 11,
        'break' => false
    ];
    public $sp;
    public $linesLength = 1;
    public $similiar = '';

    function __construct( array $data, string $template, array $config = []  ){
        $this->templateArr = explode( "\n", $template ); //    exploded by new line in linux
        $this->isMulti = isset( $data[0] ) && is_array( $data[0] );
        $this->data = $this->isMulti? $data : [$data];
        $this->config = array_merge($this->config, $config);
        $this->fontSize = $this->config[ 'fontSize'];
        $this->break = $this->config[ 'break'];
    }

    
    public function render()
    {
        $htmlArray = [];
        foreach( $this->data as $index => $dt ){
            $htmlArray[] = $this->generateHtml( $dt );
        }
        return $this->break ? 
            implode('<div style="margin-top:55px;">', $htmlArray)."<br><br>": 
            implode("<br><br>", $htmlArray)."<br><br>";
    }

    protected function generateRows( array $dataArray ) : array
    {
        $totalTable = [];
        $this->similiar = "";
        uksort($dataArray,function ($a,$b){
            return strlen($b)-strlen($a);
        });
        foreach($dataArray as $index => $rowData){
            if(is_array($rowData) && isset($rowData[0])){
                foreach(array_keys($rowData[0]) as $key){
                    $dataArray["sum.$index.$key"] = array_sum(array_column($rowData,$key));
                }
            }
        }
        foreach($this->templateArr AS $i => $dt){
            if( isset($this->templateArr[$i+1]) && $this->templateArr[$i+1]==$dt &&  $dt!==$this->similiar){
                $this->similiar  = $dt;
                foreach($dataArray as $dataIndex => $rowData){
                    if(is_array($rowData) && strpos($dt,'$'.$dataIndex.".")!==false ){
                        foreach( $rowData as $keyBaris=>$valueData ){
                            $originaldt = $dt;
                            uksort($valueData,function ($a,$b){
                                return strlen($b)-strlen($a);
                            });

                            foreach( $valueData as $keyCol=>$valueCol ){
                                if( is_array( $valueCol ) ){
                                    continue;
                                }
                                
                                $valueCol=$valueCol==""||$valueCol===null?" ":$valueCol;
                                $originaldt = str_replace('$'.$dataIndex.".".$keyCol, $valueCol, $originaldt);
                                $originaldt = str_replace("_number", $keyBaris+1, $originaldt);
                            }

                            foreach( $dataArray as $dataIndexHeader => $header ){
                                if( is_array( $header ) ){
                                    continue;
                                }
                                
                                $header=$header==""||$header===null?" ":$header;
                                $originaldt = str_replace('$'.$dataIndexHeader, $header, $originaldt);
                                $originaldt = $originaldt==""?"?":$originaldt;
                            }
                            
                            $perCols = explode( "\t",$originaldt );
                            $actualRows[] = $perCols; 
                            if( strpos( $originaldt, "!" )!==false ){
                                $indexSeru = [];
                                foreach($perCols as $idx => $colku){
                                    if(strpos($colku,'!')!==false){
                                        $indexSeru[]  = $idx;
                                    }
                                }
                                $indexDataConfig = count($actualRows);
                                if($indexDataConfig>1){
                                    $ketemu = true;
                                    for($iData = $indexDataConfig-2; $iData>=0;$iData--){
                                        $isSimiliar = false;
                                        foreach($indexSeru as $idx){
                                            if( $actualRows[$iData][$idx] =="?"){
                                                break;
                                            }else{
                                                if($actualRows[$iData] [$idx] == $actualRows[$indexDataConfig-1][$idx]){
                                                    $isSimiliar=true;
                                                }else{
                                                    $isSimiliar=false;
                                                    break 2;
                                                }
                                            }
                                        }
                                        if($isSimiliar){
                                            foreach($indexSeru as $idx){
                                                $actualRows[$indexDataConfig-1] [$idx]="?";
                                            }
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }elseif($dt!==$this->similiar){
                $this->similiar  = $dt;
                    
                foreach($dataArray as $dataIndexHeader => $header){
                    if( is_array($header) ){
                        continue;
                    }
                    $header=$header==""||$header===null?" ":$header;
                    $dt = str_replace('$'.$dataIndexHeader, $header, $dt);
                }
                $perCols = explode( "\t", $dt );
                $actualRows[] = $perCols;
                $spacing=true;    
                foreach($perCols as $col){
                    if($col!=""){
                        $spacing=false;
                        break;
                    }
                }        
            }
            
            if( $spacing === true ){
                $totalTable[] = $actualRows;
                $actualRows = [];
            }
        }
        return $totalTable;
    }

    protected function generateHtml( array $dataArray ) : string
    {
        $this->similiar = "";
        $parser = new StdMathParser();
        $evaluator = new Evaluator();
        $allPages = $this->generateRows( $dataArray );

        $html="";
        foreach($allPages as $table){
            $trs="";
            foreach($table as $i => $baris){
                $tr='<tr style="height:10px;">_tds_</tr>';
                $tds="";
                foreach($baris as $j => $col){ 
                    $td='<td style="_style_" colspan="_colspan_" rowspan="_rowspan_">_data_</td>';
                    $colspan = 1; 
                    $rowspan = 1;
                    $style = "padding-left:3px;padding-right:3px;";
                    $borderStyling=false;
                    if($col!="?"){
                        if($col!=""){
                            $colConfig = "";
                            if(strpos($col,"::")!==false){
                                $colArray = explode("::", $col);
                                $colConfig = strtolower($colArray[1]);
                                // $col = $colArray[0];
                                if(strpos($colConfig,".")!==false){
                                    try{
                                        $col = $col==0?$col:($parser->parse($colArray[0]))->accept(new Evaluator());
                                    }catch(Exception $e){
                                        $col = $colArray[0];
                                    }
                                }else{
                                    $col = $colArray[0];
                                }
                                if(strpos($colConfig,"b")!==false){
                                    $style .= "font-weight:bold;";
                                }
                                if(strpos($colConfig,"c")!==false){
                                    $style .= "text-align:center;";
                                }
                                if(strpos($colConfig,"r")!==false){
                                    $style .= "text-align:right;";
                                }                    
                                if(strpos($colConfig,"l")!==false){
                                    $style .= "text-align:left;";
                                }                  
                                if(strpos($colConfig,"v")!==false){
                                    $col = '<span style="writing-mode: tb-rl;transform: rotate(-180deg);">'.$col.'</span>';
                                }
                                if(strpos($colConfig,"_")!==false){
                                    $style .= "border-bottom:1px solid black;";$borderStyling=true;
                                }
                                if(strpos($colConfig,"-")!==false){
                                    $style .= "border-top:1px solid black;";$borderStyling=true;
                                }              
                                if(strpos($colConfig,"[")!==false){
                                    $style .= "border-left:1px solid black;";$borderStyling=true;
                                }       
                                if(strpos($colConfig,"[")!==false){
                                    $style .= "border-right:1px solid black;";$borderStyling=true;
                                }        
                                if(strpos($colConfig,"g")!==false){
                                    $style .= "background-color:#dad4d4;";
                                }    
                                if(strpos($colConfig,"y")!==false){
                                    $style .= "background-color:#f2ee74;";
                                }  
                                if(strpos($colConfig,"t")!==false){
                                    $borderStyling=true;
                                }
                                if(strpos($colConfig,"+")!==false||$borderStyling===false){
                                    $style .= "border-bottom:1px solid black;border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;";
                                }
                                if(strpos($colConfig,"=")!==false){
                                    if(isset($_POST['type']) && $_POST['type']=='html'){
                                        $style=str_replace(['1px solid '],[" double "],$style);
                                    }
                                }
                                if(strpos($colConfig,"u")!==false){
                                    $style.="text-decoration: underline;";
                                }
                                if(strpos($colConfig,"i")!==false){
                                    $style.="font-style: italic;";
                                }
                                if(strpos($colConfig,"h")!==false){
                                    $style.="font-weight: bold;";
                                }
                                
                                if(strpos($colConfig,"w")!==false){
                                    $temp = explode("w",$colConfig);
                                    $temp = explode("%",$temp[1])[0];
                                    $style .= "width:".$temp."%;";
                                }
                                if(strpos($col,"!")!==false){
                                    $col =str_replace("!","",$col);
                                }
                                if(strpos($colConfig,".")!==false){
                                    $col = number_format($col,2,",",".");
                                }
                            }else{
                                if(strpos($col,"!")!==false){
                                    $col =str_replace("!","",$col);
                                }
                                $style.="border-bottom:1px solid black;border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;";
                            }
                            $style.="font-size: $this->fontSize;";
            
                            foreach($baris as $colindex => $mycol){
                                if($colindex<=$j){continue;}
                                if( $mycol=='' && ((isset($table[$i-1][$colindex]) && $table[$i-1][$colindex]=='') || !isset($table[$i-1][$colindex]) ) ){
                                    $colspan ++;
                                }else{
                                    break;
                                }
                            }
            
                            if( strpos( $colConfig, "h" ) ){
                                $td = str_replace( ["<td","td>"], ["<th","th>"], $td );
                            }
                            foreach( $table as $myindex => $mybaris){
                                if($myindex<=$i){continue;}
                                if( @$mybaris[$j]==""){
                                    $col='<div style="font-size:5pt">&nbsp;</div>'.$col;
                                    $rowspan++;
                                }else{
                                    break;
                                }
                            }
            
                        }
                    }else{
                        $style="";
                        $col="&nbsp;";
                    }
                    
                    if( substr($col,0,1) == "!"){
                        $col=preg_replace('/!/', '', $col, 1); 
                    }
                    $td = str_replace([
                        "_data_","_rowspan_","_colspan_","_style_"
                    ], [
                        $col,$rowspan,$colspan,$style
                    ], $td );
                    if($col!=""){
                        $tds .= $td;
                    }
                }
                $tr = str_replace(["_tds_"],[$tds],$tr);
                if($tr=== '<tr style="height:10px;"></tr>' ){
                    continue;
                }
                $trs.=$tr;
            }
            $html .= '<div><table style="width:100%;border-collapse: collapse;" cellspacing=0>'.$trs.'</table></div>';
        }
        
        return $html;
    }
}
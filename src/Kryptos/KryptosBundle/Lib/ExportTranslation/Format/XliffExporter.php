<?php
namespace Kryptos\KryptosBundle\Lib\ExportTranslation\Format;

use Kryptos\KryptosBundle\Lib\ExportTranslation\FileExporter;

class XliffExporter extends FileExporter
{
	public function outputHeader()
	{
		$output = <<<DATA
<?xml version="1.0"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" datatype="plaintext" original="file.ext">
        <body>
DATA;
		
		file_put_contents($this->getFilePath(), $output);
	}
	
	
	public function outputMainData()
	{
		foreach ($this->getData() as $key => $item)
		{
			$count = $key+1;			
			$output = <<<DATA
			
			<trans-unit id="{$count}">
                <source>{$item['name']}</source>
                <target><![CDATA[{$item['value']}]]></target>
            </trans-unit>
DATA;
			
			file_put_contents($this->getFilePath(), $output, FILE_APPEND);
		}
	}
	
	
	
	public function outputFooter()
	{
		$output = <<<DATA
		
        </body>
    </file>
</xliff>
DATA;
		
		file_put_contents($this->getFilePath(), $output, FILE_APPEND);
	}
	
	
}
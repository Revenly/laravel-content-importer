<?php

namespace R64\ContentImport\Tests\Unit;

use Illuminate\Http\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use R64\ContentImport\Processors\TxtProcessor;
use R64\ContentImport\Tests\TestCase;

class TxtProcessorTest extends TestCase
{

    /** @test */
    public function it_can_process_txt_files()
    {
        Storage::disk('local')->putFileAs('imports/1/', new File('tests/files/test_import.txt'), 'test_import.txt');

        $rows = (new TxtProcessor())->read('imports/1/test_import.txt', '|');

        $this->assertTrue(is_array($rows));
        $this->assertCount(2, $rows);

        $first = Arr::first($rows);

        $this->assertEquals($first,  [
                "AccountNumber" => "50451",
                "RecipientName" => "  UNPOSTED CASH",
                "Address1" => "SEE COMMENTS FOR",
                "Address2" => "PAYMENTS NOT POSTED",
                "City" => "MADISON",
                "State" => "WI",
                "Zip" => "",
                "Balance" => "0.00",
                "RemoveAccount" => "Y",
                "Client" => "42341",
                "ClientName" => "STATE COLLECTION SERVICE",
                "ConsumerAccount" => "50451-",
                "ClientModel" => "",
                "ClientAccountNumber" => "UC",
                "PatientName" => "  UNPOSTED CASH",
                "DueDate" => "",
                "AdmitDate" => "",
                "DischargeDate" => "",
                "LastName" => "UNPOSTED CASH",
                "Element1" => "",
                "TotalDue" => "0.00",
                "PaymentTypeCode" => "",
                "FEE" => "Y",
                "Phase" => "50"
            ]
        );

        Storage::disk('local')->delete('imports/1/test_import.txt');
    }

    /** @test */
    public function it_can_process_txt_files_with_different_delimeters()
    {
        Storage::disk('local')->putFileAs('imports/1/', new File('tests/files/test_import_2.txt'), 'test_import_2.txt');

        $rows = (new TxtProcessor())->read('imports/1/test_import_2.txt', '>');

        $this->assertTrue(is_array($rows));
        $this->assertCount(2, $rows);

        $first = Arr::first($rows);

        $this->assertEquals($first,  [
                "AccountNumber" => "50451",
                "RecipientName" => "  UNPOSTED CASH",
                "Address1" => "SEE COMMENTS FOR",
                "Address2" => "PAYMENTS NOT POSTED",
                "City" => "MADISON",
                "State" => "WI",
                "Zip" => "",
                "Balance" => "0.00",
                "RemoveAccount" => "Y",
                "Client" => "42341",
                "ClientName" => "STATE COLLECTION SERVICE",
                "ConsumerAccount" => "50451-",
                "ClientModel" => "",
                "ClientAccountNumber" => "UC",
                "PatientName" => "  UNPOSTED CASH",
                "DueDate" => "",
                "AdmitDate" => "",
                "DischargeDate" => "",
                "LastName" => "UNPOSTED CASH",
                "Element1" => "",
                "TotalDue" => "0.00",
                "PaymentTypeCode" => "",
                "FEE" => "Y",
                "Phase" => "50"
            ]
        );

        Storage::disk('local')->delete('imports/1/test_import_2.txt');
    }


}

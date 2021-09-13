<?php

namespace R64\ContentImport\Tests\Unit;

use Illuminate\Http\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use R64\ContentImport\Processors\FileProcessor;
use R64\ContentImport\Tests\TestCase;

class FileProcessorTest extends TestCase
{

    /** @test */
    public function it_can_process_txt_files()
    {
        Storage::disk('local')->putFileAs('imports/1/', new File('tests/files/test_import.txt'), 'test_import.txt');

        $rows = (new FileProcessor())->read('imports/1/test_import.txt', '|');
        $outPut = [];

        foreach ($rows as $row) {
            array_push($outPut, $row->all());
        }

        $this->assertInstanceOf(\Generator::class, $rows);

        $this->assertCount(2, $outPut[0]);

        $first = Arr::first($outPut[0]);

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

        $rows = (new FileProcessor())->read('imports/1/test_import_2.txt', '>');

        $outPut = [];

        foreach ($rows as $row) {
            array_push($outPut, $row->all());
        }

        $this->assertInstanceOf(\Generator::class, $rows);

        $this->assertCount(2, $outPut[0]);

        $first = Arr::first($outPut[0]);

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

    /** @test */
    public function it_can_process_csv_files()
    {
        Storage::disk('local')->putFileAs('imports/1/', new File('tests/files/test_import.csv'), 'test_import.csv');

        $rows = (new FileProcessor())->read('imports/1/test_import.csv', ',');

        $outPut = [];

        foreach ($rows as $row) {
            array_push($outPut, $row->all());
        }

        $this->assertInstanceOf(\Generator::class, $rows);

        $this->assertCount(99, $outPut[0]);

        $first = Arr::first($outPut[0]);

        $this->assertEquals($first,  [
            "RecordType" => "Account",
              "ConsumerFirstName" => "Test",
              "ConsumerLastName" => "Testfeild",
              "StreetName" => "62 North Central Dr.",
              "City" => "Ofallon",
              "State" => "MO",
              "ZipCode" => "63366",
              "Last4ofSSN" => "1111",
              "DateofBirth" => "",
              "FileNumber" => "1000",
              "DebtorID" => "44444",
              "OriginalAccountNumber" => "1000",
              "PhoneNumber" => "6363856698",
              "OriginalCreditorName" => "",
              "EmailAddress" => "Drew.christmas@deltaoutsourcegroup.com",
              "AccountBalance" => "110",
              "AccountStatus" => "ACT",
              "ProductType" => "00014 - BANK",
              "ClientName" => "Old National Bank",
              "NextPaymentDueDate" => "",
              "NextPaymentDueAmount" => "",
              "LastPaymentDate" => "",
              "LastPaymentAmount" => "0",
              "TotalPaid" => "0",
              "PaymentOption" => "0",
              "TokenID" => "LAT106801",
              "CustomerField1" => "Old National Bank",
            ]
        );

        Storage::disk('local')->delete('imports/1/test_import_2.txt');
    }
}

<?php

namespace R64\ContentImport\Tests\Unit;

use Illuminate\Http\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use League\Csv\MapIterator;
use R64\ContentImport\Processors\FileProcessor;
use R64\ContentImport\Tests\TestCase;

class FileProcessorTest extends TestCase
{

    /** @test */
    public function it_can_process_txt_files()
    {
        Storage::disk('local')->putFileAs('imports/1/', new File('tests/files/test_import.txt'), 'test_import.txt');

        $rows = (new FileProcessor())->read('imports/1/test_import.txt', '|');
        $outPut = collect($rows);


        $this->assertInstanceOf(MapIterator::class, $rows);

        $this->assertCount(2, $outPut);

        $this->assertEquals($outPut->first(),  [
              "Account Number" => "50451",
              "Recipient Name" => "  UNPOSTED CASH",
              "Address1" => "SEE COMMENTS FOR",
              "Address2" => "PAYMENTS NOT POSTED",
              "City" => "MADISON",
              "State" => "WI",
              "Zip" => "",
              "Balance" => "0.00",
              "Remove Account" => "Y",
              "Client" => "42341",
              "Client Name" => "STATE COLLECTION SERVICE",
              "Consumer Account" => "50451-",
              "Client Model" => "",
              "Client Account Number" => "UC",
              "Patient Name" => "  UNPOSTED CASH",
              "Due Date" => "",
              "Admit Date" => "",
              "Discharge Date" => "",
              "Last Name" => "UNPOSTED CASH",
              "Element 1" => "",
              "Total Due" => "0.00",
              "Payment Type Code" => "",
              "FEE" => "Y",
              "Phase" => "50",
            ]
        );

        Storage::disk('local')->delete('imports/1/test_import.txt');
    }

    /** @test */
    public function it_can_process_txt_files_with_different_delimeters()
    {
        Storage::disk('local')->putFileAs('imports/1/', new File('tests/files/test_import_2.txt'), 'test_import_2.txt');

        $rows = (new FileProcessor())->read('imports/1/test_import_2.txt', '>');

        $outPut = collect($rows);

        $this->assertInstanceOf(MapIterator::class, $rows);

        $this->assertCount(2, $outPut);

        $this->assertEquals($outPut->first(),  [
              "Account Number" => "50451",
              "Recipient Name" => "  UNPOSTED CASH",
              "Address1" => "SEE COMMENTS FOR",
              "Address2" => "PAYMENTS NOT POSTED",
              "City" => "MADISON",
              "State" => "WI",
              "Zip" => "",
              "Balance" => "0.00",
              "Remove Account" => "Y",
              "Client" => "42341",
              "Client Name" => "STATE COLLECTION SERVICE",
              "Consumer Account" => "50451-",
              "Client Model" => "",
              "Client Account Number" => "UC",
              "Patient Name" => "  UNPOSTED CASH",
              "Due Date" => "",
              "Admit Date" => "",
              "Discharge Date" => "",
              "Last Name" => "UNPOSTED CASH",
              "Element 1" => "",
              "Total Due" => "0.00",
              "Payment Type Code" => "",
              "FEE" => "Y",
              "Phase" => "50",
            ]
        );

        Storage::disk('local')->delete('imports/1/test_import_2.txt');
    }

    /** @test */
    public function it_can_process_csv_files()
    {
        Storage::disk('local')->putFileAs('imports/1/', new File('tests/files/test_import.csv'), 'test_import.csv');

        $rows = (new FileProcessor())->read('imports/1/test_import.csv', ',');

        $outPut = collect($rows);

        $this->assertInstanceOf(MapIterator::class, $rows);

        $this->assertCount(231, $outPut);

        $this->assertEquals($outPut->first(),  [
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
              "CustomerField1" => "Old National Bank"
            ]
        );

        Storage::disk('local')->delete('imports/1/test_import_2.txt');
    }
}

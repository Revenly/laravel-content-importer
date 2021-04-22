<?php
namespace Tests;

use R64\ContentImport\MapImportedContent;

class MapImportedContentTest extends TestCase
{
    protected $data;

    protected $mapImportedContent;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [
            [
                "city" => "Ofallon",
                "state" => "MO",
                "tokenid" => "LAT106801",
                "zipcode" => 63366,
                "debtorid" => 44444,
                "totalpaid" => 0,
                "clientname" => "Old National Bank",
                "filenumber" => 1000,
                "last4ofssn" => 1111,
                "recordtype" => "Account",
                "streetname" => "62 North Central Dr.",
                "dateofbirth" => null,
                "phonenumber" => 6363856698,
                "producttype" => "00014 - BANK",
                "emailaddress" => "drew.christmas@deltaoutsourcegroup.com",
                "accountstatus" => "ACT",
                "paymentoption" => 0,
                "accountbalance" => 110,
                "customerfield1" => "Old National Bank",
                "lastpaymentdate" => null,
                "consumerlastname" => "Testfeild",
                "consumerfirstname" => "Test",
                "lastpaymentamount" => 0,
                "nextpaymentduedate" => null,
                "nextpaymentdueamount" => null,
                "originalcreditorname" => null,
                "originalaccountnumber" => 1000,

            ]
        ];

        $this->mapImportedContent = (new MapImportedContent($this->data));
    }

    // Mockery
}

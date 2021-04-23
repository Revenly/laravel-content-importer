<?php
namespace R64\ContentImport\Tests;

use Illuminate\Database\Eloquent\Model;
use R64\ContentImport\MapImportedContent;
use R64\ContentImport\Validations\CleanseEmail;
use R64\ContentImport\Validations\Concerns\LowerCaseString;
use R64\ContentImport\Validations\Concerns\RemoveInvalidCharacters;
use R64\ContentImport\Validations\Concerns\TrimString;

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

    /** @test */
    public function can_cleanse_an_email_with_classes()
    {
        $this->data = [
            [
                "emailaddress" => "DRew.christmas@deltaoutsourcegroup.com,$",
            ]
        ];

        $this->mapImportedContent = (new MapImportedContent($this->data));

        $contents = $this->mapImportedContent->withMappedRow([
            Model::class => [
                'email' => 'emailaddress',
            ]
        ])
            ->withUniqueFields([
                Model::class => [
                    'email',
                ]
            ])
            ->withCasting([
                Model::class => [
                    'email' => CleanseEmail::class,
                ]
            ])
            ->map()
            ->getMappedRows();

        $this->assertEquals('drew.christmas@deltaoutsourcegroup.com', $contents[0]['data'][Model::class]['email']);
    }

    /** @test */
    public function can_cleanse_an_email_with_array_of_concerns()
    {
        $this->data = [
            [
                "emailaddress" => "DRew.christmas@deltaoutsourcegroup.com,$",
            ]
        ];

        $this->mapImportedContent = (new MapImportedContent($this->data));

        $contents = $this->mapImportedContent->withMappedRow([
            Model::class => [
                'email' => 'emailaddress',
            ]
        ])
            ->withUniqueFields([
                Model::class => [
                    'email',
                ]
            ])
            ->withCasting([
                Model::class => [
                    'email' => [
                        TrimString::class,
                        LowerCaseString::class,
                        RemoveInvalidCharacters::class
                    ]
                ]
            ])
            ->map()
            ->getMappedRows();

        $this->assertEquals('drew.christmas@deltaoutsourcegroup.com', $contents[0]['data'][Model::class]['email']);
    }
}

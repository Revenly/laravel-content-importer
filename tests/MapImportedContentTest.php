<?php

namespace R64\ContentImport\Tests;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use R64\ContentImport\MapImportedContent;
use R64\ContentImport\Castings\CleanseEmail;
use R64\ContentImport\Castings\Concerns\LowerCaseString;
use R64\ContentImport\Castings\Concerns\RemoveInvalidCharacters;
use R64\ContentImport\Castings\Concerns\TrimString;
use R64\ContentImport\Events\ValidationFailed;
use R64\ContentImport\Exceptions\ValidationFailedException;
use R64\ContentImport\Validations\Concerns\IsValidEmail;
use R64\ContentImport\Validations\ValidEmail;

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

        $this->mapImportedContent = (new MapImportedContent)->init($this->data);
    }

    /** @test */
    public function can_cleanse_an_email_with_classes()
    {
        $this->data = [
            [
                "emailaddress" => "DRew.christmas@deltaoutsourcegroup.com,$",
            ]
        ];

        $this->mapImportedContent = (new MapImportedContent)->init($this->data);

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

        $this->mapImportedContent = (new MapImportedContent)->init($this->data);

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

    /** @test */
    public function casting_should_run_if_validation_is_empty()
    {
        $this->data = [["email" => "JohDOe@email.com"]];

        $this->mapImportedContent = (new MapImportedContent)->init($this->data);

        $result = $this->mapImportedContent
            ->withMappedRow([
                Model::class => [
                    'email' => 'email'
                ]
            ])->withValidations([
                Model::class => []
            ])
            ->withCasting([
                Model::class => [
                    'email' => [LowerCaseString::class]
                ]
            ])
            ->map()
            ->getMappedRows();

        $this->assertEquals('johdoe@email.com', $result[0]['data'][Model::class]['email']);
    }


    /** @test */
    public function can_validate_an_email()
    {
        $this->data = [
            [
                "email" => "ab",
                "phone" => "",
                'name' => '2342342'
            ]
        ];

        $this->mapImportedContent = (new MapImportedContent)->init($this->data);

        $this->mapImportedContent
            ->withMappedRow([
                Model::class => [
                    'email' => 'ok',
                    'phone' => 'phone',
                ],
                Customer::class => [
                    'name' => 'name'
                ]
            ])
            ->withValidations([
                Model::class => [
                    'email' => [IsValidEmail::class],
                    'phone' => fn ($value) => $value !== ''
                ],
                Customer::class => [
                    'name' => fn ($value) => $value !== ''
                ]
            ])
            ->map();

        $this->assertCount(1, $this->mapImportedContent->getDirtyRows());
        $this->assertCount(1, $this->mapImportedContent->getMappedRows());
    }
}

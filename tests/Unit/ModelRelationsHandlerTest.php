<?php

namespace Drewlabs\Packages\Database\Tests\Unit;

use Drewlabs\Packages\Database\Tests\Stubs\Address;
use Drewlabs\Packages\Database\TouchedModelRelationsHandler;
use Drewlabs\Packages\Database\Tests\Stubs\Individual;
use Drewlabs\Packages\Database\Tests\Stubs\Manager;
use Drewlabs\Packages\Database\Tests\Stubs\Member;
use Drewlabs\Packages\Database\Tests\Stubs\Moral;
use Drewlabs\Packages\Database\Tests\Stubs\Person;
use Drewlabs\Packages\Database\Tests\Stubs\Post;
use Drewlabs\Packages\Database\Tests\Stubs\Video;
use Drewlabs\Packages\Database\Tests\TestCase;
use Illuminate\Database\Eloquent\Collection;

use function Drewlabs\Packages\Database\Proxy\DMLManager;

class ModelRelationsHandlerTest extends TestCase
{
    public function test_model_relation_handler_create()
    {
        // Test to ensure data were not in the database at first
        $this->assertNull(Member::where('phonenumber', '407-925-1076')->where('email', 'DavidPThompson@dayrep.com')->get()->first());
        // First we insert the individual model to the database
        $model = Individual::create([
            'firstname' => 'David',
            'lastname' => 'P. Thompson',
            'address' => '1237 McDonald Avenue',
            'sex' => 'M',
        ]);
        $model = TouchedModelRelationsHandler::new($model)->create(['member'], [
            'firstname' => 'David',
            'lastname' => 'P. Thompson',
            'address' => '1237 McDonald Avenue',
            'sex' => 'M',
            'member' => [
                'phonenumber' => '407-925-1076',
                'email' => 'DavidPThompson@dayrep.com'
            ]
        ]);
        $this->assertNotNull(Member::where('phonenumber', '407-925-1076')->where('email', 'DavidPThompson@dayrep.com')->get()->first());
        $this->assertNotNull(Individual::where('firstname', 'David')->where('address', '1237 McDonald Avenue')->get()->first());
    }


    public function test_model_relation_handler_create_moral_create_morph_member()
    {
        // Test to ensure data were not in the database at first
        $this->assertNull(Member::where('phonenumber', '954-438-2314')->where('email', 'azlabsjs@drewlabs.com')->get()->first());
        // First we insert the individual model to the database
        $model = Moral::create([
            'label' => 'AZLAB\'s Ltd.',
            'address' => '3378 West Fork Drive',
        ]);
        $model = TouchedModelRelationsHandler::new($model)->create(['member'], [
            'label' => 'AZLAB\'s Ltd.',
            'address' => '3378 West Fork Drive',
            'member' => [
                'phonenumber' => '954-438-2314',
                'email' => 'azlabsjs@drewlabs.com'
            ]
        ]);
        $this->assertNotNull(Member::where('phonenumber', '954-438-2314')->where('email', 'azlabsjs@drewlabs.com')->get()->first());
        $this->assertNotNull(Moral::where('label', 'AZLAB\'s Ltd.')->get()->first());
    }


    public function test_model_relation_handler_create_on_morph_many()
    {
        $post = Post::create([
            'title' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry',
            'body' => 'Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.'
        ]);
        TouchedModelRelationsHandler::new($post)->create(['comments'], [
            'comments' => [
                [
                    'body' => 'it is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using \'Content here, content here\', making it look like readable English.'
                ],
                [
                    'body' => 'There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don\'t look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn\'t anything embarrassing hidden in the middle of text.'
                ]
            ]
        ]);
        /**
         * @var Collection
         */
        $comments = Post::find($post->getKey())->comments;
        $this->assertEquals(2, $comments->count());

        $video = Video::create([
            'title' => 'Funny Videos',
            'url' => 'https://tinyurl.com/2p99ztyj'
        ]);

        TouchedModelRelationsHandler::new($video)->create(['comments'], [
            'comments' => [
                [
                    'body' => 'Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old'
                ],
            ]
        ]);
        /**
         * @var Collection
         */
        $comments = Video::find($video->getKey())->comments;
        $this->assertNotEquals(3, $comments->count());
        $this->assertEquals(1, $comments->count());
    }


    public function test_model_relation_handler_create_on_morph_many_to_many()
    {
        $post = Post::create([
            'title' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry',
            'body' => 'Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.'
        ]);
        TouchedModelRelationsHandler::new($post)->create(['tags'], [
            'tags' => [
                [
                    'name' => 'warning',
                    'pivot' => [
                        'score' => 7
                    ]
                ],
                [
                    'name' => 'success',
                    'pivot' => [
                        'score' => 12
                    ]
                ]
            ]
        ]);
        /**
         * @var Collection
         */
        $tags = Post::find($post->getKey())->tags;
        $this->assertEquals(2, $tags->count());
    }

    public function test_model_relation_handler_update()
    {
        // First we insert the individual model to the database
        $model = Individual::create([
            'firstname' => 'David',
            'lastname' => 'P. Thompson',
            'address' => '1237 McDonald Avenue',
            'sex' => 'M',
        ]);
        TouchedModelRelationsHandler::new($model)->create(['member'], [
            'firstname' => 'David',
            'lastname' => 'P. Thompson',
            'address' => '1237 McDonald Avenue',
            'sex' => 'M',
            'member' => [
                'phonenumber' => '407-925-1076',
                'email' => 'DavidPThompson@dayrep.com'
            ]
        ]);
        TouchedModelRelationsHandler::new($model)->update(['member'], [
            'member' => [
                'email' => 'KatherineWSykora@dayrep.com'
            ]
        ], true);
        $member = Member::where('email', 'KatherineWSykora@dayrep.com')->get()->first();
        $this->assertNotNull($member);
        $this->assertEquals($model->getKey(), $member->distinctable_id);
    }

    public function test_model_relation_handler_update_modify_matching_instances()
    {
        $dmlManager = DMLManager(Person::class);
        $person = $dmlManager->create(
            [
                'firstname' => 'Katherine',
                'lastname' => ' W. Sykora',
                'phonenumber' => '216-355-2334',
                'age' => 66,
                'sex' => 'F',
                'addresses' => [
                    [
                        'postal_code' => 'Independence, OH 44131',
                        'country' => 'USA',
                        'city' => 'Washington DC',
                        'email' => 'KatherineWSykora@dayrep.com',
                    ],
                    [
                        'postal_code' => 'Pinellas, FL 34624',
                        'country' => 'USA',
                        'city' => 'Pinellas',
                        'email' => 'lordfera2@gmail.com',
                    ],
                ],
            ],
            [
                'relations' => [
                    'addresses',
                ],
            ]
        );

        DMLManager(Person::class)->update(
            $person->getKey(),
            [
                'addresses' => [
                    [
                        ['email' => 'KatherineWSykora@dayrep.com'],
                        ['postal_code' => 'Montezuma, GA 31063', 'email' => 'LuanneCCardillo@armyspy.com']
                    ],
                    [
                        ['email' => 'lordfera2@gmail.com'],
                        ['postal_code' => 'Longmont, CO 80501']
                    ]
                ]
            ],
            [
                'relations' => ['addresses']
            ]
        );
        $this->assertTrue(
            Address::where('postal_code', 'Montezuma, GA 31063')
                ->where('email', 'LuanneCCardillo@armyspy.com')
                ->where('person_id', $person->getKey())->first() !== null
        );
        $this->assertTrue(
            Address::where('postal_code', 'Longmont, CO 80501')
                ->where('email', 'lordfera2@gmail.com')
                ->where('person_id', $person->getKey())->first() !== null
        );
    }

    public function test_model_relation_handler_create_on_belongs_to_many()
    {

        DMLManager(Person::class)->create([
            'firstname' => 'Linda',
            'lastname' => 'R. Jones',
            'phonenumber' => '863-676-8520',
            'age' => 41,
            'sex' => 'M',
            'is_active' => true,
            'managers' => [
                'name' => 'Alan G. Waite',
                'position' => 'Private banker'
            ]
        ], [
            'relations' => ['managers']
        ]);
        $this->assertNotNull(Manager::where('name', 'Alan G. Waite')->where('position', 'Private banker')->first(), 'Expected attached manager to exists in the managers table');

        DMLManager(Person::class)->create([
            'firstname' => 'David',
            'lastname' => 'C. Lester',
            'phonenumber' => '813-387-0501',
            'age' => 85,
            'sex' => 'M',
            'managers' => [
                [
                    'name' => 'Jordan A. Donaghy',
                    'position' => 'Dermatology nurse',
                    'pivot' => [
                        'department' => 'MEDECINE'
                    ]
                ],
                [
                    'name' => 'Jenae S. Patrick',
                    'position' => 'Pediatry',
                    'pivot' => [
                        'department' => 'MEDECINE'
                    ]
                ]
            ]
        ], [
            'relations' => ['managers']
        ]);
        $manager = Manager::where('name', 'Jordan A. Donaghy')->where('position', 'Dermatology nurse')->first();
        $this->assertNotNull($manager, 'Expected attached manager to exists in the managers table');
        $this->assertEquals('MEDECINE', $manager->person_manager->department);
    }
}

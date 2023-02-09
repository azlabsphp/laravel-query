<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Packages\Database\Tests;

use Drewlabs\Contracts\Data\Filters\FiltersInterface;
use Drewlabs\Contracts\Data\Parser\ModelAttributeParser as ModelAttributesParserContract;
use Drewlabs\Packages\Database\Contracts\TransactionUtils;
use Drewlabs\Packages\Database\DatabaseTransactionManager;
use Drewlabs\Packages\Database\ModelAttributesParser;
use Drewlabs\Packages\Database\QueryFilters;
use Drewlabs\Packages\Database\Tests\Stubs\Address;
use Drewlabs\Packages\Database\Tests\Stubs\Person;
use Drewlabs\Packages\Database\Tests\Stubs\Profil;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase as FrameworkTestCase;

class TestCase extends FrameworkTestCase
{
    protected function setUp(): void
    {
        $container = Container::getInstance();
        $this->registerBindings($container);
        $this->configureDatabase();
        $this->migrateIdentitiesTable();
    }

    public function migrateIdentitiesTable()
    {
        Manager::schema()->create('persons', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('firstname', 100);
            $table->string('lastname', 100);
            $table->string('phonenumber', 20)->nullable();
            $table->string('email', 190)->nullable();
            $table->unsignedTinyInteger('age');
            $table->boolean('is_active')->nullable()->default(0);
            $table->enum('sex', ['M', 'F']);
            $table->timestamps();
        });

        Manager::schema()->create('managers', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('position');
            $table->timestamps();
        });

        Manager::schema()->create('person_managers', static function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('person_id');
            $table->unsignedInteger('manager_id');
            $table->string('department', 100)->nullable();
            $table->foreign('person_id')->references('id')->on('persons');
            $table->foreign('manager_id')->references('id')->on('managers');
            $table->timestamps();
        });

        Manager::schema()->create('addresses', static function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('person_id');
            $table->string('postal_code', 255);
            $table->string('country', 100);
            $table->string('city', 100);
            $table->string('email', 190)->nullable();
            $table->foreign('person_id')->references('id')->on('persons')->onDelete('CASCADE');
            $table->timestamps();
        });

        Manager::schema()->create('profiles', static function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('person_id');
            $table->text('url');
            $table->foreign('person_id')->references('id')->on('persons')->onDelete('CASCADE');
            $table->timestamps();
        });

        // Morph tables definitions
        Manager::schema()->create('individuals', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('firstname', 50);
            $table->string('lastname', 50);
            $table->string('address')->nullable();
            $table->char('sex', 1);
            $table->timestamps();
        });

        Manager::schema()->create('morals', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('label', 100);
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Manager::schema()->create('members', static function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('distinctable_id');
            $table->string('distinctable_type', 255);
            $table->string('phonenumber', 20);
            $table->string('email', 190);
            $table->timestamps();
        });

        // #region Post - Video - Comments
        Manager::schema()->create('posts', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });

        Manager::schema()->create('videos', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('url');
            $table->timestamps();
        });

        Manager::schema()->create('comments', static function (Blueprint $table) {
            $table->increments('id');
            $table->text('body');
            $table->unsignedBigInteger('commentable_id');
            $table->string('commentable_type', 255);
            $table->timestamps();
        });

        Manager::schema()->create('tags', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Manager::schema()->create('taggables', static function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('tag_id');
            $table->unsignedBigInteger('taggable_id');
            $table->string('taggable_type');
            $table->integer('score');
            $table->timestamps();
        });
        // #endregion Posts - Video - Comments

        // Seed table with default values
        $p1 = Person::create(
            [
                'firstname' => 'BENJAMIN',
                'lastname' => 'PAYARO',
                'phonenumber' => '+22890002345',
                'age' => 24,
                'sex' => 'M',
                'is_active' => 0,
                'email' => 'benjaminpayaro@gmail.com',
            ],
            true,
            false,
            []
        );
        $p2 = Person::create(
            [
                'firstname' => 'SIDOINE',
                'lastname' => 'AZOMEDOH',
                'phonenumber' => '+22891002345',
                'age' => 28,
                'sex' => 'M',
                'is_active' => 1,
                'email' => 'azandrewdevelopper@gmail.com',
            ],
            true,
            false,
            []
        );
        Address::create([
            'postal_code' => 'BP 25 LOME - TOGO',
            'country' => 'TOGO',
            'city' => 'LOME',
            'email' => 'azandrew@liksoft.tg',
            'person_id' => $p2->getKey(),
        ]);

        Address::create([
            'postal_code' => 'BP 228 LOME - TOGO',
            'country' => 'TOGO',
            'city' => 'LOME',
            'email' => 'benjamin-p@liksoft.tg',
            'person_id' => $p1->getKey(),
        ]);
    }

    public function registerBindings(Container $app)
    {
        $app->singleton('db.factory', static function ($app) {
            return new ConnectionFactory($app);
        });
        $app->singleton('db', static function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });
        $app->singleton(TransactionUtils::class, static function ($app) {
            return new DatabaseTransactionManager();
        });
        $app->bind(FiltersInterface::class, QueryFilters::class);
        $app->bind(ModelAttributesParserContract::class, ModelAttributesParser::class);
    }

    /**
     * @test
     */
    public function init()
    {
        $this->assertTrue(true);
    }

    protected function profilFactory()
    {
        return new class() {
            /**
             * Creates an instance of the Profil model.
             *
             * @return Profil
             */
            public function make(array $attributes = [])
            {
                return new Profil($attributes);
            }

            /**
             * Creates and instance of Profil object and persist it to the database.
             *
             * @return Profil
             */
            public function create(array $attributes)
            {
                return Profil::create($attributes);
            }
        };
    }

    protected function addressFactory()
    {
        return new class() {
            /**
             * Creates an instance of the Address model.
             *
             * @return Address
             */
            public function make(array $attributes = [])
            {
                return new Address($attributes);
            }

            /**
             * Creates and instance of Address object and persist it to the database.
             *
             * @return Address
             */
            public function create(array $attributes)
            {
                return Address::create($attributes);
            }
        };
    }

    protected function personFactory()
    {
        return new class() {
            /**
             * Creates an instance of the person model.
             *
             * @return Person
             */
            public function make(array $attributes = [])
            {
                return new Person($attributes);
            }

            /**
             * Creates and instance of Person object and persist it to the database.
             *
             * @return Person
             */
            public function create(array $attributes)
            {
                return Person::create($attributes);
            }
        };
    }

    protected function configureDatabase()
    {
        $db = new Manager();
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ]);
        $db->bootEloquent();
        $db->setAsGlobal();
    }
}

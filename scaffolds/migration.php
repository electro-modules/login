<?php
use Electro\Interfaces\UserInterface;
use Electro\Plugins\IlluminateDatabase\AbstractMigration;
use Electro\Plugins\Login\Services\User;
use Illuminate\Database\Schema\Blueprint;

class __CLASS__ extends AbstractMigration
{
  /**
   * Reverse the migration.
   *
   * @return void
   */
  function down ()
  {
    $schema = $this->db->schema ();

    // Pick one of the alternatives below (and delete the other one):

    // Drop the table created on up()
    if ($schema->hasTable ('users'))
      $schema->drop ('users');
  }

  /**
   * Run the migration.
   *
   * @return void
   */
  function up ()
  {
    $schema = $this->db->schema ();

    // Pick one of the alternatives below (and delete the other one):

    // Create a new table
    if (!$this->db->hasTable ('users')) {
      $schema->create ('users', function (Blueprint $t) {
        $t->increments ('id');
        $t->timestamp (User::CREATED_AT)->useCurrent ();
        $t->timestamp (User::UPDATED_AT)->nullable ();
        $t->timestamp ('lastLogin')->nullable ();
        $t->string ('username', 30)->unique ()->nullable ();
        $t->string ('email', 100)->unique ();
        $t->string ('password', 60);
        $t->string ('realName', 30);
        $t->tinyInteger ('role');
        $t->tinyInteger ('enabled')->default (true);
        $t->boolean ('active')->default (false);
        $t->string ('token', 100);
      });
      $now = date ('Y-m-d H:i:s', time () - 3600);
      $this->db->table ('users')->insert ([
        'username'         => 'admin',
        'password'         => '',
        'realName'         => 'Admin',
        'email'            => 'admin',
        'role'             => UserInterface::USER_ROLE_DEVELOPER,
        User::CREATED_AT   => $now,
        User::UPDATED_AT   => $now,
        'registrationDate' => $now,
        'active'           => true,
        'enabled'          => true,
        'token'            => '',
      ]);
    }
  }
}

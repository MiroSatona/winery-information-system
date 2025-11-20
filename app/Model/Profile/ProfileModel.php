<?php

//FILE:     ProfileModel.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Model for user profile.

namespace App\Model\Profile;

use Nette\Database\Explorer;
use Nette\Security\Passwords;


/**
 * Repository for handling user profiles
 */
class ProfileModel
{
    /** @var Explorer $database Database of the system */
    private Explorer $database;
    /** @var Passwords $password Passwords service for hashing and verifying passwords */
    private Passwords $password;

    /**
     * Constructor for the ProfileModel
     *
     * @param Explorer $database Database of the system.
     * @param integer $id Id of profile which will be edited.
     */
    public function __construct(Explorer $database, Passwords $passwords)
    {
        $this->database = $database;
        $this->password = $passwords;
    }
    
    /**
     * editUserBySelf
     * Method for model handle update with database for user change of his profile.
     *
     * @param array $newData Array of data for user profile update.
     * @param int $id Id of the user whose profile will be updated.
     * @return string Empty string if everything is OK, else error message.
     */
    public function editUserBySelf(array $newData, int $id): string{
        /** @var string $resultMessage Message with error message, or empty if everything will be right during the run method. */
        $resultMessage = '';

        // Begin transaction for updating user profile
        $this->database->beginTransaction();
        try 
        {   
            // Update user by provided data
            $this->database->table('USER')->where('ID', $id)->update($newData);
            $this->database->commit();
        } catch (\Exception $databaseException) 
        {
            $this->database->rollBack();
            $resultMessage = 'Změnu se nepodařilo provést v databázi.';
        }
        return $resultMessage;
    }
    /**
     * resetPasswordByUser
     * Method of model for change password, where change is provided by user.
     *
     * @param string $oldPassword Old password of the user.
     * @param string $newPassword New password which user want
     * @param int $id Id of the user whose password will be changed.
     * @return string Empty string if everything is OK, else error message.
     */
    public function resetPasswordByUser(string $oldPassword, string $newPassword, int $id): string
    {   
        /** @var string $resultMessage Message with error message, or empty if everything will be right during the run method. */
        $resultMessage = '';

        /** @var string $oldPasswordHash Hash of the old password with salt. */
        $oldPasswordHash = $this->database->table('USER')->where('ID', $id)->fetch()['PSWD_HASH'];
        /** @var string $saltOfOldPassword Salt of the old password. */
        $saltOfOldPassword = $this->database->table('USER')->where('ID', $id)->fetch()['PSWD_SALT'];
        /** @var string $saltedPassword Old password salted with salt from database. */
        $saltedPassword = $oldPassword . $saltOfOldPassword;

        // Verify old password
        if (!$this->password->verify($saltedPassword, $oldPasswordHash)) {
            $resultMessage = 'Staré heslo není správné.';
            return $resultMessage;
        }

        // Begin transaction for 
        $this->database->beginTransaction();
        try
        {
            /** @var string $newSalt New salt for the new password. */
            $newSalt = bin2hex(random_bytes(16));
            /** @var string $saltedNewPassword New password salted with new salt. */
            $saltedNewPassword = $newPassword . $newSalt;
            /** @var string $hashedNewPassword Hashed new password with salt. */
            $hashedNewPassword = $this->password->hash($saltedNewPassword);

            // Update the users password in the database
            $this->database->table('USER')->where('ID', $id)->update([
                'PSWD_SALT' => $newSalt,
                'PSWD_HASH' => $hashedNewPassword,
            ]);

            $this->database->commit();
        } catch (\Exception $databaseException) 
        {
            $this->database->rollBack();
            $resultMessage = 'Změnu hesla se nepodařilo provést v databázi.';
        }
        return $resultMessage;
    }
}

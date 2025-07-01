<?php
namespace App\Model;

use App\Core\Model;
use App\Core\Model\Collection;
use App\Core\Model\CollectionInterface;

class Account extends Model
{

    const TABLE = 'account';

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    
    protected string $table = self::TABLE;

    
    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function getEmail(): ?string
    {
        return $this->get('email');
    }

    public function getPassword(): ?string
    {
        return $this->get('password');
    }

    /**
     * Hash the password
     *
     * @param string $password The password to hash
     * @return string The hashed password
     */
    public static function hashPassword(string $password): string
    {
        /**
            @todo: password strength validation
         */

        if (empty($password)) {
            throw new \InvalidArgumentException('Password cannot be empty.');
        }

        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function isAdmin(): bool
    {
        return (bool)$this->get('is_admin', false);
    }

    public function isActive(): bool
    {
        return (bool)$this->get('status', self::STATUS_INACTIVE);
    }

    public function isSubscribed(): bool
    {
        return (bool)$this->get('subscribed', false);
    }

    public function setSubscribed(bool $subscribed, string $subscriptionId): static
    {
        $this->set('subscribed', $subscribed ? 1 : 0);
        $this->set('subscription_id', $subscriptionId);
        
        return $this;
    }

    /**
     * Find an account by email
     *
     * @param string $email The email to search for
     */
    public function findByEmail(string $email): static
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email");
        $stmt->execute(['email' => $email]);
        
        return (new static())->setData($stmt->fetch() ?: []);
    }

    /**
     * @deprecated
     * Load account data by email
     *
     * @param string $email The email to search for
     * @return self|null Returns the Account object if found, null otherwise
     */
    public function loadByEmail(string $email): ?self
    {
        return $this->findByEmail($email);
    }   
    
    /**
     * Find an account by reset token
     *
     * @param string $token The reset token to search for
     * 
     * @return static Returns the Account object if found, empty object otherwise
     */
    public function findByResetToken(string $token): static
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE reset_token = :token");
        $stmt->execute(['token' => $token]);

        return (new static())->setData($stmt->fetch() ?: []);
    }

    


    public function getParcels(): CollectionInterface
    {
        $parcelCollection = (new Parcel())
            ->getCollection()
            ->setItemMode(Collection::ITEM_MODE_OBJECT)
            ->addFilter(['account_id' => $this->getId()])
            ->sort('created_at', 'DESC');

        return $parcelCollection;
    }

    /**
     * Create a reset token for the account
     *
     * @return string The generated reset token
     */
    public function createResetToken(): string
    {
        $token = bin2hex(random_bytes(16));
        $this->set('reset_token', $token);
        $this->save();

        return $token;
    }

    /**
     * Reset the account password using a reset token
     *
     * @param string $token The reset token
     * @param string $newPassword The new password
     * @param string $confirmPassword The confirmation of the new password
     * @return bool Returns true if the password was reset successfully
     * @throws \InvalidArgumentException If the passwords do not match or the token is invalid
     */
    public function resetPassword(string $token, string $newPassword, string $confirmPassword): bool
    {
        if (empty($newPassword) || empty($confirmPassword)) {
            throw new \InvalidArgumentException('New password and confirmation cannot be empty.');
        }

        if (strlen($newPassword) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long.');
        }

        if ($newPassword !== $confirmPassword) {
            throw new \InvalidArgumentException('Passwords do not match.');
        }

        if ($this->get('reset_token') !== $token) {
            throw new \InvalidArgumentException('Invalid reset token.');
        }

        $this->set('password', Account::hashPassword($newPassword));
        $this->set('reset_token', null); // Clear the reset token after use

        $this->save();

        return true;
    }

    /**
     * Fill billing address fields if they are empty
     *
     * @param array $data The data to fill
     * @return array The filled data
     */
    public static function fillBilling(array $data): array
    {
        $data['billing_street'] = empty($data['billing_street']) ? $data['street'] : $data['billing_street'];
        $data['billing_city'] = empty($data['billing_city']) ? $data['city'] : $data['billing_city'];
        $data['billing_state'] = empty($data['billing_state']) ? $data['state'] : $data['billing_state'];
        $data['billing_zip'] = empty($data['billing_zip']) ? $data['zip'] : $data['billing_zip'];

        return $data;
    }
}

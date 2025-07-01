<?php

namespace App\Model\Account;

use App\Core\Model\Collection;
use App\Core\Request;
use App\Model\Account;
use App\Model\Parcel;

class User
{
    private static ?User $instance = null;
    private ?Request $request = null;
    private Account $accountModel;

    /**
     * 
     */
    private function __construct()
    {
        $this->request = Request::getInstance();

        $this->accountModel = new Account();
        
        if ($this->isLoggedIn()) {
            $this->accountModel->load($this->request->session('uid'));
        }
        
    }

    /**
     * Get the singleton instance of User
     *
     * @return User
     */
    protected function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get the singleton instance of User.
     *
     * @return User
     */
    public static function getInstance(): User
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get the account model of the logged-in user.
     *
     * @return Account
     */
    public function getAccount(): Account
    {
        return $this->accountModel;
    }

    /**
     * Get the account model of the logged-in user.
     *
     * @return Account
     */
    public static function Account(): Account
    {
        return self::getInstance()->getAccount();
    }

    /**
     * Get the ID of the logged-in user.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->getAccount()?->getId();
    }

    /**
     * Check if the user is logged in.
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return null !== $this->getRequest()->getSession('uid');
    }

    /**
     * @deprecated
     */
    public function hasParcels(): bool
    {
        $parcels = (new Parcel())->getCollection();
        $parcels->setItemMode(Collection::ITEM_MODE_ARRAY);

        return $parcels->addFilter(['account_id' => $this->getId()])->count() > 0;
    }

    public static function uid(): ?int
    {
        return self::getInstance()->getId();
    }

    public static function isAdmin(): bool
    {
        return self::getInstance()->getAccount()->isAdmin();
    }

    public static function isActingAsUser(): bool
    {
        return self::getInstance()->getRequest()->getSession('acting_as_user') ?? false;
    }

}
<?php

namespace App\Jobs;

use Model\AuctionModel;

class ProcessAuctions
{
    private AuctionModel $auctionModel;

    public function __construct(?AuctionModel $auctionModel = null)
    {
        $this->auctionModel = $auctionModel ?? new AuctionModel();
    }

    public function handle(): void
    {
        $this->auctionModel->doAuction();
    }
}

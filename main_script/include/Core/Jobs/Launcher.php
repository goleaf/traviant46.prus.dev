<?php

namespace Core\Jobs;

use Core\Queue\QueueManager;
use Model\AdventureModel;
use Model\AuctionModel;
use Model\AutoExtendModel;
use Model\DailyQuestModel;
use Model\FakeUserModel;
use Model\MedalsModel;
use Model\NatarsModel;
use Model\inactiveModel;

class Launcher
{
    private static ?self $instance = null;

    public static function lunchJobs(): void
    {
        set_time_limit(0);
        ini_set('memory_limit', -1);
        QueueManager::boot();

        foreach (self::getInstance()->allJobs() as $job) {
            $job->dispatch();
        }
    }

    private function allJobs(): array
    {
        return array_merge(
            $this->resourceTick(),
            $this->buildComplete(),
            $this->movementComplete(),
            $this->trainingComplete(),
            $this->gameProgress(),
            $this->routineJobs(),
            $this->AIProgress(),
            $this->postService()
        );
    }

    public static function getInstance(): self
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @return Job[]
     */
    private function buildComplete(): array
    {
        $jobs = [
            Job::automation('marketComplete:tradeRoute', 5, 'tradeRoutes'),
            Job::automation('marketComplete:researchComplete', 3, 'researchComplete'),
            Job::automation('marketComplete:marketComplete', 2, 'marketComplete'),
            Job::model('auctionComplete:doAuction', 5, AuctionModel::class, 'doAuction'),
            Job::model('routineJobs:fakeAuction', 600, AuctionModel::class, 'fakeAuction'),
            Job::automation('buildComplete:buildComplete', 1, 'buildComplete'),
        ];

        if (function_exists('getCustom') && getCustom('autoRaidEnabled')) {
            $jobs[] = Job::automation('autoFarmlist', 30, 'autoFarmlist');
        }

        return $jobs;
    }

    /**
     * @return Job[]
     */
    private function movementComplete(): array
    {
        return [
            Job::automation('movementComplete:attackMovementComplete', 0, 'attackMovementComplete'),
            Job::automation('movementComplete:otherMovementComplete', 0, 'otherMovementComplete'),
        ];
    }

    /**
     * @return Job[]
     */
    private function trainingComplete(): array
    {
        return [
            Job::automation('trainingComplete:trainingComplete', 1, 'trainingComplete'),
        ];
    }

    /**
     * @return Job[]
     */
    private function gameProgress(): array
    {
        return [
            Job::automation('gameProgress:checkAutoFinish', 30, 'checkAutoFinish'),
            Job::automation('gameProgress:setUpNewServer', 60, 'setUpNewServer'),
            Job::automation('gameProgress:boughtGoldMessage', 10, 'boughtGoldMessage'),
            Job::automation('gameProgress:banProgress', 120, 'banProgress'),
            Job::automation('gameProgress:referenceCheck', 30, 'referenceCheck'),
            Job::model('gameProgress:ArtifactReleases', 30, NatarsModel::class, 'runJobs'),
            Job::model('gameProgress:resetMedals', 30, MedalsModel::class, 'resetMedals'),
            Job::model('gameProgress:resetDailyQuest', 30, DailyQuestModel::class, 'resetDailyQuest'),
            Job::model('generalProgress:autoExtend', 60, AutoExtendModel::class, 'processAutoExtend'),
            Job::automation('generalProgress:updateFoolArtifact', 120, 'updateFoolArtifact'),
            Job::automation('generalProgress:checkForArtifactActivation', 5, 'checkForArtifactActivation'),
            Job::automation('generalProgress:cleanupServer', 300, 'cleanupServer'),
            Job::automation('loyaltyAndCulturePoint:deleteOasisComplete', 60, 'deleteOasisComplete'),
            Job::automation('gameProgress:allianceBonus', 60, 'handleAllianceBonusTasks'),
            Job::automation('generalProgress:zeroPopVillages', 10, 'zeroPopVillages'),
        ];
    }

    /**
     * @return Job[]
     */
    private function routineJobs(): array
    {
        return [
            Job::model('routineJobs:checkForNewAdventures', 10, AdventureModel::class, 'checkForNewAdventures'),
            Job::automation('checkIndexFunctions:checkGameFinish', 30, 'checkGameFinish'),
            Job::model('checkIndexFunctions:clearAndDeleting', 30, inactiveModel::class, 'startWorker'),
            Job::automation('checkIndexFunctions:cleanupIndex', 600, 'cleanupIndex'),
            Job::automation('mayExitJobs:refreshCountryFlag', 100, 'refreshCountryFlag'),
            Job::automation('mayExitJobs:resetDailyGold', 45, 'resetDailyGold'),
            Job::automation('mayExitJobs:backup', 360, 'backup'),
        ];
    }

    /**
     * @return Job[]
     */
    private function AIProgress(): array
    {
        $jobs = [];

        $jobs[] = Job::model('AIProgress:handleFakeUsers', 45, FakeUserModel::class, 'handleFakeUsers');
        $jobs[] = Job::model('AIProgress:handleFakeUserExpands', 45, FakeUserModel::class, 'handleFakeUserExpands');
        $jobs[] = Job::model('AIProgress:handleNatarVillages', 15, NatarsModel::class, 'handleNatarVillages');
        $jobs[] = Job::model('AIProgress:handleNatarExpansion', 15, NatarsModel::class, 'handleNatarExpansion');
        $jobs[] = Job::model('AIProgress:handleNatarDefense', 30, NatarsModel::class, 'handleNatarDefense');

        return $jobs;
    }

    /**
     * @return Job[]
     */
    private function postService(): array
    {
        return [Job::automation('postService', 100, 'postService')];
    }

    /**
     * @return Job[]
     */
    private function resourceTick(): array
    {
        return [
            Job::automation('resources:tick', 1, 'resourceTick'),
        ];
    }
}

<?php

namespace App\Managers;

use App\Constants\ReportCategory;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Repositories\ReportRepository;

class ReportManager extends AManager {
    private ReportRepository $reportRepository;
    private UserManager $userManager;

    public function __construct(Logger $logger, EntityManager $entityManager, ReportRepository $reportRepository, UserManager $userManager) {
        parent::__construct($logger, $entityManager);

        $this->reportRepository = $reportRepository;
        $this->userManager = $userManager;
    }

    public function reportUserForUsingBannedWord(string $bannedWord, string $userId) {
        try {
            if($this->isUserAlreadyReported($userId)) {
                throw new GeneralException('User already reported.');
            }

            $systemUserId = $this->getSystemUserId();

            if(!$this->reportRepository->createUserReport($systemUserId, $userId, ReportCategory::OTHER, 'User used a banned word.')) {
                throw new GeneralException('Could not report user.');
            }
        } catch(AException $e) {
            throw $e;
        }
    }

    private function getSystemUserId() {
        try {
            $user = $this->userManager->getUserByUsername('service_user');
        } catch(AException $e) {
            throw $e;
        }

        return $user->getId();
    }

    private function isUserAlreadyReported(string $userId) {
        $reports = $this->reportRepository->getUserReportsForUser($userId);

        return !empty($reports);
    }
}

?>
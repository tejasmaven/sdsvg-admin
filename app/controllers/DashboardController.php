<?php
/**
 * DashboardController
 */
class DashboardController extends BaseController
{
    /** Age threshold (years) for the "senior" cards. */
    private const SENIOR_AGE = 70;
    /** Age threshold (years, exclusive) for the "kids" card. */
    private const KID_AGE = 18;

    /**
     * GET /dashboard
     */
    public function index(): void
    {
        $db = Database::getInstance();

        // Family / member totals
        $totalFamilies = (int) $db->query("SELECT COUNT(*) FROM `groups`")->fetchColumn();
        $totalMembers  = (int) $db->query("SELECT COUNT(*) FROM members")->fetchColumn();

        // Gender + age breakdown in one pass. Age is only counted when a DOB exists;
        // gender is matched by the seeded lookup names (Male / Female).
        $sql = "
            SELECT
                SUM(g.name = 'Male')   AS male,
                SUM(g.name = 'Female') AS female,
                SUM(g.name = 'Male'   AND m.dob IS NOT NULL AND TIMESTAMPDIFF(YEAR, m.dob, CURDATE()) > :senior1) AS male_senior,
                SUM(g.name = 'Female' AND m.dob IS NOT NULL AND TIMESTAMPDIFF(YEAR, m.dob, CURDATE()) > :senior2) AS female_senior,
                SUM(m.dob IS NOT NULL AND TIMESTAMPDIFF(YEAR, m.dob, CURDATE()) < :kid)  AS kids,
                SUM(m.dob IS NULL) AS no_dob,
                SUM(m.education IS NULL OR TRIM(m.education) = '') AS no_education,
                SUM(m.mobile    IS NULL OR TRIM(m.mobile)    = '') AS no_mobile,
                SUM(m.email     IS NULL OR TRIM(m.email)     = '') AS no_email
            FROM members m
            JOIN genders g ON g.gender_id = m.gender_id
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':senior1' => self::SENIOR_AGE,
            ':senior2' => self::SENIOR_AGE,
            ':kid'     => self::KID_AGE,
        ]);
        $b = $stmt->fetch() ?: [];

        // Families inside vs outside Surat
        $cityRow     = $db->query("SELECT SUM(is_in_surat) AS in_surat, COUNT(*) AS total FROM `groups`")->fetch() ?: [];
        $inSurat     = (int) ($cityRow['in_surat'] ?? 0);
        $outsideSurat = (int) ($cityRow['total'] ?? 0) - $inSurat;

        // Families with no home address recorded
        $noAddress = (int) $db->query(
            "SELECT COUNT(*) FROM `groups` WHERE address IS NULL OR TRIM(address) = ''"
        )->fetchColumn();

        // Pending public edit requests + headless families (data-health)
        $pendingReviews   = (new EditRequestModel())->countPending();
        $headlessFamilies = (new GroupModel())->getHeadlessFamilies();

        $this->render('dashboard/index', [
            'totalFamilies'    => $totalFamilies,
            'totalMembers'     => $totalMembers,
            'totalMale'        => (int) ($b['male'] ?? 0),
            'totalFemale'      => (int) ($b['female'] ?? 0),
            'maleSenior'       => (int) ($b['male_senior'] ?? 0),
            'femaleSenior'     => (int) ($b['female_senior'] ?? 0),
            'kids'             => (int) ($b['kids'] ?? 0),
            'noDob'            => (int) ($b['no_dob'] ?? 0),
            'noEducation'      => (int) ($b['no_education'] ?? 0),
            'noMobile'         => (int) ($b['no_mobile'] ?? 0),
            'noEmail'          => (int) ($b['no_email'] ?? 0),
            'noAddress'        => $noAddress,
            'inSurat'          => $inSurat,
            'outsideSurat'     => $outsideSurat,
            'pendingReviews'   => $pendingReviews,
            'headlessFamilies' => $headlessFamilies,
            'seniorAge'        => self::SENIOR_AGE,
            'kidAge'           => self::KID_AGE,
            'pageTitle'        => 'Dashboard',
        ]);
    }
}

<?php
/**
 * PdfController — on-demand PDF generation
 */
class PdfController extends BaseController
{
    /**
     * POST /pdf/generate
     */
    public function generate(): void
    {
        $this->request->validateCsrf();

        $builder = new PdfBuilder();
        $builder->build(); // streams to browser, exits
    }

    /**
     * GET /pdf/preview2 — show all family data with a Download PDF button
     */
    public function preview2(): void
    {
        $memberModel  = new MemberModel();
        $settingModel = new SettingModel();
        $allMembers   = $memberModel->getAllForPdf();
        $settings     = $settingModel->getAll();

        // Group members by family (order preserved from query)
        $families = [];
        foreach ($allMembers as $m) {
            $families[$m['group_id']][] = $m;
        }

        $this->render('pdf/preview2', [
            'families'  => $families,
            'settings'  => $settings,
            'pageTitle' => 'PDF 2 — Preview',
        ]);
    }

    /**
     * POST /pdf/generate2 — card-style portrait layout (max 3 families/page)
     */
    public function generate2(): void
    {
        $this->request->validateCsrf();

        $builder = new PdfBuilder2();
        $builder->build(); // streams to browser, exits
    }
}

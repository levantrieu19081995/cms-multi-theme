<?php
// Chạy bằng: DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan tinker --execute="require '<this file>';"
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CmsBackend\Enums\SurveyStatus;
use Modules\CmsBackend\Exceptions\SurveyRuleException;
use Modules\CmsBackend\Services\SurveyService;

if (config('database.default') !== 'sqlite') { echo "REFUSED: not sqlite\n"; return; }
foreach (glob(base_path('database/migrations/2026_10_31_00000*.php')) as $f) { (require $f)->up(); }
// chạy lần 2 để chắc chắn idempotent
foreach (glob(base_path('database/migrations/2026_10_31_00000*.php')) as $f) { (require $f)->up(); }
echo "migrations ok (twice)\n";

$svc = app(SurveyService::class);
$ok = function (string $name, bool $cond) { echo ($cond ? 'PASS' : 'FAIL')." - $name\n"; };

$s = $svc->create(['ks_ten' => 'Khảo sát thử', 'ks_so_lan_gui' => 1, 'ks_gioi_thieu' => '<p>Hi</p><script>alert(1)</script><a onclick="x()" href="javascript:alert(1)">l</a>'], 1);
$ok('slug generated', $s->ks_ma === 'khao-sat-thu');
$ok('draft', $s->status() === SurveyStatus::Draft);
$ok('script stripped', ! str_contains($s->ks_gioi_thieu, 'script') && ! str_contains($s->ks_gioi_thieu, 'onclick') && ! str_contains($s->ks_gioi_thieu, 'javascript:'));
$s2 = $svc->create(['ks_ten' => 'Khảo sát thử'], 1);
$ok('slug unique', $s2->ks_ma === 'khao-sat-thu-2');

$groups = [[
    'name' => 'Phần A', 'questions' => [
        ['type' => 'single', 'text' => 'Q1', 'required' => true, 'options' => [['text' => 'A'], ['text' => 'B'], ['text' => '']]],
        ['type' => 'multiple', 'text' => 'Q2', 'options' => [['text' => 'x'], ['text' => 'y']]],
        ['type' => 'scale', 'text' => 'Q3', 'config' => ['min' => 1, 'max' => 5]],
        ['type' => 'text', 'text' => 'Q4'],
        ['type' => 'yesno', 'text' => 'Q5'],
    ],
], ['name' => 'Phần B', 'questions' => [['type' => 'textarea', 'text' => 'Q6']]]];
$svc->saveStructure($s, $groups, 1);
$st = $svc->structureForBuilder($s->fresh());
$ok('2 groups', count($st) === 2);
$ok('5 questions in A', count($st[0]['questions']) === 5);
$ok('empty option dropped', count($st[0]['questions'][0]['options']) === 2);
$ok('questions have codes', DB::table('ks_cau_hoi')->whereNull('ch_ma')->count() === 0);
$ok('order stored', DB::table('ks_cau_hoi')->orderBy('ch_thu_tu')->pluck('ch_noidung')->all() === ['Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'Q6']);

// validation
foreach ([
    'one option' => [['name' => 'G', 'questions' => [['type' => 'single', 'text' => 'x', 'options' => [['text' => 'a']]]]]],
    'no name' => [['name' => '', 'questions' => []]],
    'bad type' => [['name' => 'G', 'questions' => [['type' => 'zzz', 'text' => 'x']]]],
    'bad scale' => [['name' => 'G', 'questions' => [['type' => 'scale', 'text' => 'x', 'config' => ['min' => 5, 'max' => 3]]]]],
] as $label => $bad) {
    try { $svc->saveStructure($s, $bad, 1); $ok("rejects $label", false); } catch (SurveyRuleException) { $ok("rejects $label", true); }
}

// reorder + keep ids: move Q2 first
$st = $svc->structureForBuilder($s->fresh());
$q1 = $st[0]['questions'][0]['id']; $q2 = $st[0]['questions'][1]['id'];
$st[0]['questions'] = array_values(array_reverse($st[0]['questions']));
$svc->saveStructure($s, $st, 1);
$ok('ids stable after reorder', DB::table('ks_cau_hoi')->where('ch_id', $q1)->exists() && DB::table('ks_cau_hoi')->count() === 6);

// publish
try { $svc->transition($s2, SurveyStatus::Open, 1); $ok('empty survey cannot publish', false); } catch (SurveyRuleException) { $ok('empty survey cannot publish', true); }
$s = $svc->transition($s->fresh(), SurveyStatus::Open, 1);
$ok('open + legacy flag', $s->ks_status === 'open' && (bool) $s->ks_trang_thai === true);
try { $svc->saveStructure($s, $st, 1); $ok('structure locked when open', false); } catch (SurveyRuleException) { $ok('structure locked when open', true); }
$svc->update($s, ['ks_ten' => 'Đổi tên', 'ks_ma' => 'doi-slug'], 1);
$ok('slug immutable after publish', $s->fresh()->ks_ma === 'khao-sat-thu');
try { $svc->transition($s->fresh(), SurveyStatus::Draft, 1); $ok('no back to draft', false); } catch (SurveyRuleException) { $ok('no back to draft', true); }
try { $svc->delete($s->fresh()); $ok('cannot delete open', false); } catch (SurveyRuleException) { $ok('cannot delete open', true); }
$s = $svc->transition($s->fresh(), SurveyStatus::Paused, 1);
$s = $svc->transition($s->fresh(), SurveyStatus::Open, 1);
$s = $svc->transition($s->fresh(), SurveyStatus::Ended, 1);
try { $svc->update($s, ['ks_ten' => 'x'], 1); $ok('ended is read-only', false); } catch (SurveyRuleException) { $ok('ended is read-only', true); }
try { $svc->transition($s->fresh(), SurveyStatus::Open, 1); $ok('ended cannot reopen', false); } catch (SurveyRuleException) { $ok('ended cannot reopen', true); }

// duplicate
$copy = $svc->duplicate($s->fresh(), 1);
$ok('copy is draft with new slug', $copy->ks_status === 'draft' && $copy->ks_ma !== $s->ks_ma);
$ok('copy has structure', DB::table('ks_cau_hoi')->where('ks_id', $copy->ks_id)->count() === 6 && DB::table('ks_nhom')->where('ks_id', $copy->ks_id)->count() === 2);
$ok('copy codes differ', DB::table('ks_cau_hoi')->distinct()->count('ch_ma') === 12);
$ok('option copies', DB::table('ks_tra_loi')->where('ch_id', DB::table('ks_cau_hoi')->where('ks_id', $copy->ks_id)->where('ch_noidung', 'Q1')->value('ch_id'))->count() === 2);

// answered question is retired, not deleted
$qa = DB::table('ks_cau_hoi')->where('ks_id', $copy->ks_id)->where('ch_noidung', 'Q1')->first();
$oa = DB::table('ks_tra_loi')->where('ch_id', $qa->ch_id)->first();
DB::table('ks_khao_sat_users')->insert(['ks_id' => $copy->ks_id, 'ksu_ma' => 'x', 'ksu_ho_ten' => '', 'ksu_so_dien_thoai' => '', 'ksu_trang_thai' => 'completed']);
DB::table('ks_ket_qua')->insert(['ks_id' => $copy->ks_id, 'ch_id' => $qa->ch_id, 'ksu_id' => 1, 'trl_id' => $oa->trl_id, 'kskq_trl_value' => 'v']);
$st = $svc->structureForBuilder($copy->fresh());
$st[0]['questions'] = array_values(array_filter($st[0]['questions'], fn ($q) => $q['text'] !== 'Q1')); // gỡ Q1
$svc->saveStructure($copy->fresh(), $st, 1);
$ok('answered question soft-deleted, still in DB', DB::table('ks_cau_hoi')->where('ch_id', $qa->ch_id)->whereNotNull('ch_deleted_at')->exists());
$ok('its options kept', DB::table('ks_tra_loi')->where('ch_id', $qa->ch_id)->count() === 2);
$ok('unanswered removal is physical', DB::table('ks_cau_hoi')->where('ks_id', $copy->ks_id)->count() === 6);

// safe delete
$svc->delete($copy->fresh());
$ok('soft deleted survey keeps rows', DB::table('ks_khao_sat')->where('ks_id', $copy->ks_id)->whereNotNull('ks_deleted_at')->exists() && DB::table('ks_ket_qua')->count() === 1);
echo "done\n";

<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_CAPTCHA_PATH.'/captcha.lib.php');

// 세션 멤버 정보
global $member;

// 제목 자동생성
$subject = date("Y-m-d H:i:s")." 문의";

// 게시판 설정 불러오기
$board = get_board_db($bo_table);
$cfg = [];
for($idx=1; $idx<=10; $idx++) {
    $key = 'bo_'.$idx.'_subj';
    if($board[$key]) $cfg[$board[$key]] = $board['bo_'.$idx];
}

// 문의분류 재생성 [공지]제거
$is_category = false;
if ($board['bo_use_category']) {
    $category_list = explode('|',$board['bo_category_list']);
    $is_category = true;
}

$is_file = false;
if ($member['mb_level'] >= $board['bo_upload_level']) {
    $is_file = true;
}
$file_count = (int)$board['bo_upload_count'];

/**
 * 경로 설정
 */
$board_skin_name = $rows;
$board_skin_path = '';

// 테마가 아닐경우
if(strpos($board_skin_name, 'theme') === false) {
    $board_skin_path = G5_SKIN_PATH.'/board'.str_replace('theme', '', $board_skin_name);
} else {
    $board_skin_path = G5_THEME_PATH.'/skin/board'.str_replace('theme', '', $board_skin_name);
}

// 개인정보 처리방침
$privacy_html = file_get_contents($board_skin_path.'/privacy.html');
foreach (explode('|',$cfg['개인정보처리방침']) as $idx=>$v) {
    $privacy_html = str_replace("{{{$idx}}}", $v, $privacy_html);
}

// 캡챠 무조건 사용하도록 하기
$is_use_captcha = true;
$captcha_html = captcha_html();
$captcha_js   = chk_captcha_js();

$action_url = https_url(G5_BBS_DIR)."/write_update.php";

add_stylesheet('<link rel="stylesheet" href="'.$latest_skin_url.'/style.css">', 0);
?>
<form name="fwrite" id="fwrite" action="<?php echo $action_url ?>"
      onsubmit="return fwrite_submit(this);"
      method="post" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="uid" value="<?php echo get_uniqid()?>">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="wr_subject" value="<?php echo $subject ?>">
    <input type="hidden" name="ret_url" value="<?php echo $_SERVER['REQUEST_URI']?>">

    <!--
    폼메일 입력 구조를 정의합니다.
    입력받을 데이터를 name|항목이름 순으로 입력합니다.
    -->
    <input type="hidden" name="contents_info[]" value="wr_name|성함">
    <input type="hidden" name="contents_info[]" value="wr_email|이메일">
    <input type="hidden" name="contents_info[]" value="wr_1|연락처">
    <input type="hidden" name="contents_info[]" value="wr_2|직책">
    <input type="hidden" name="contents_info[]" value="ca_name|문의종류">
    <input type="hidden" name="contents_info[]" value="wr_content|문의내용">

    <section id="formmail-write">
        <div class="form-body">

            <div class="form-group col4">
                <label for="wr_name">성함</label>
                <div>
                    <input type="text" name="wr_name" value="<?php echo $member['mb_name']?>" id="wr_name" required class="form-control required" size="8" maxlength="20">
                </div>
                <label for="wr_email">이메일</label>
                <div>
                    <input type="text" name="wr_email" value="<?php echo $member['mb_email']?>" id="wr_email" class="form-control email required" size="50" maxlength="100">
                </div>
            </div>

            <div class="form-group col4">
                <label for="wr_1">연락처</label>
                <div>
                    <input type="text" name="wr_1" value="" id="wr_1" required class="form-control" size="10" maxlength="20">
                </div>
                <label for="wr_2">직책</label>
                <div>
                    <input type="text" name="wr_2" value="" id="wr_2" required class="form-control" size="10" maxlength="20">
                </div>
            </div>

            <?php if ($is_category) { ?>
                <div class="form-group">
                    <label for="ca_name">문의종류</label>
                    <div>
                        <select name="ca_name" id="ca_name" required class="form-control required">
                            <option value="">선택하세요</option>
                            <?php foreach($category_list as $v) {?>
                                <option value="<?php echo $v?>"><?php echo $v?></option>
                            <?php }?>
                        </select>
                    </div>
                </div>
            <?php } ?>

            <div class="form-group">
                <label for="wr_content">문의내용</label>
                <div>
                    <textarea id="wr_content" name="wr_content" class="form-control" maxlength="65536" cols="10000" rows="10"></textarea>
                </div>
            </div>

            <?php for ($i = 0; $is_file && $i < $file_count; $i++) { ?>
                <div class="form-group">
                    <label for="">파일 #<?php echo $i + 1 ?></label>
                    <div>
                        <input type="file" name="bf_file[]" title="파일첨부 <?php echo $i + 1 ?> : 용량 <?php echo $upload_max_filesize ?> 이하만 업로드 가능" class="form-control-file">
                        <?php if ($is_file_content) { ?>
                            <input type="text" name="bf_content[]" value="<?php echo ($w == 'u') ? $file[$i]['bf_content'] : ''; ?>" title="파일 설명을 입력해주세요." class="form-control" size="50">
                        <?php } ?>
                        <?php if ($w == 'u' && $file[$i]['file']) { ?>
                            <input type="checkbox" id="bf_file_del<?php echo $i ?>" name="bf_file_del[<?php echo $i; ?>]" value="1"> <label for="bf_file_del<?php echo $i ?>"><?php echo $file[$i]['source'] . '(' . $file[$i]['size'] . ')'; ?> 파일 삭제</label>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <div class="form-group">
                <label for="">자동등록<br/>방지</label>
                <div><?php echo $captcha_html?></div>
            </div>
        </div>

        <div class="privacy-of-use">
            <?php echo $privacy_html?>
        </div>
        <div class="privacy-of-use-check">
            <label><input type="checkbox" class="required" required/> 개인정보 처리방침에 동의합니다.</label>
        </div>

        <div class="form-footer">
            <button type="submit" id="btn_submit" class="btn_submit btn btn-primary"><i class="fa fa-paper-plane" aria-hidden="true"></i> 문의하기</button>
        </div>

    </section>
</form>

<script type="text/javascript">
    function fwrite_submit(f) {
        let subject = "";
        let content = "";
        $.ajax({
            url: g5_bbs_url + "/ajax.filter.php",
            type: "POST",
            data: {
                "subject": f.wr_subject.value,
                "content": f.wr_content.value
            },
            dataType: "json",
            async: false,
            cache: false,
            success: function(data, textStatus) {
                subject = data.subject;
                content = data.content;
            }
        });

        if (subject) {
            alert("제목에 금지단어('" + subject + "')가 포함되어있습니다");
            f.wr_subject.focus();
            return false;
        }

        if (content) {
            alert("내용에 금지단어('" + content + "')가 포함되어있습니다");
            if (typeof(ed_wr_content) != "undefined")
                ed_wr_content.returnFalse();
            else
                f.wr_content.focus();
            return false;
        }

        // 캡챠 사용시 자바스크립트에서 입력된 캡챠를 검사함
        <?php echo $captcha_js?>

        document.getElementById("btn_submit").disabled = "disabled";

        return true;
    }
</script>

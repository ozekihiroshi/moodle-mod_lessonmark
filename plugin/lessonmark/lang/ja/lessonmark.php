<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Japanese strings for LessonMark.
 *
 * @package   mod_lessonmark
 * @copyright 2026 Hiroshi Ozeki
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['calloutnote'] = '補足';
$string['callouttip'] = 'ヒント';
$string['calloutwarning'] = '注意';
$string['codeblocklabel'] = '{$a}のコード';
$string['copied'] = 'コピーしました';
$string['copyfailed'] = 'コピーできませんでした';
$string['copylatex'] = 'LaTeXをコピー';

$string['coursejavascript'] = '連続表示にはJavaScriptが必要です。教材単独のプレゼンテーションは利用できます。';
$string['courselessons'] = '教材一覧';
$string['courseloading'] = '教材を読み込んでいます…';
$string['coursenotlisted'] = 'この教材はコースの連続表示の対象外です。教材単独のプレゼンテーションを利用してください。';
$string['courseposition'] = '教材 {lesson} / {lessons} ・ スライド {slide} / {slides}';
$string['coursepresentation'] = 'コース教材を連続表示';
$string['coursereturn'] = 'コースに戻る';
$string['courseunavailable'] = '教材から応答がありません。下のメッセージを確認し、別の教材を選ぶかコースに戻ってください。';
$string['diagnosticmissingalt'] = '画像「{$a}」には空でない代替テキストが必要です。';
$string['diagnosticrelativeimage'] = '相対パス画像「{$a}」はMoodleで管理されていません。下の画像欄へアップロードし、@@PLUGINFILE@@参照を使用してください。';
$string['diagnosticrendering'] = '原稿に完全には表示できない要素があります。';
$string['diagnosticunsupportedlanguage'] = 'コード言語「{$a}」は未対応のためプレーンテキストで表示します。';
$string['editmode'] = '編集';
$string['editormodes'] = 'Markdownエディタの表示モード';
$string['errorinvalidutf8'] = 'Markdown原稿は正しいUTF-8で入力してください。';
$string['errorsourcetoolarge'] = 'Markdown原稿は512 KiB以内にしてください。';
$string['eventcoursemoduleviewed'] = 'LessonMark教材が閲覧されました';
$string['exportsavedmarkdown'] = '保存済み.mdをエクスポート';
$string['exportsavedpdf'] = '保存済みPDFをダウンロード';
$string['imagefiles'] = '教材画像';
$string['imagefiles_help'] = '画像をここへアップロードし、Markdownでは ![代替テキスト](@@PLUGINFILE@@/image.png) と参照します。ファイルマネージャに表示された正確なパスを使用してください。';
$string['imagessection'] = '画像';
$string['importconfirm'] = 'インポートすると、エディタ内の現在のMarkdownを置き換えます。続行しますか？';
$string['importerror'] = 'Markdownファイルをインポートできませんでした。';
$string['importfilelabel'] = 'インポートするMarkdownファイルを選択';
$string['importinvalidutf8'] = '選択したファイルは正しいUTF-8ではありません。';
$string['importmarkdown'] = '.mdをインポート';
$string['importready'] = 'Markdownを取り込みました。プレビューを確認し、保存して公開してください。';
$string['importtoolarge'] = '選択したMarkdownファイルは512 KiBを超えています。';
$string['importwrongtype'] = 'ファイル名が.mdで終わるファイルを選択してください。';
$string['lessonmark:addinstance'] = '新しいLessonMark教材を追加する';
$string['lessonmark:edit'] = 'LessonMark教材を編集する';
$string['lessonmark:view'] = 'LessonMark教材を閲覧する';
$string['lessonmarkname'] = '名称';
$string['markdownsource'] = 'Markdown原稿';
$string['markdownsource_help'] = 'Markdown原稿を正本として保存し、表示時にレンダリングします。raw HTMLは文字として表示します。';
$string['mathsourceblock'] = '数式の原稿';
$string['mermaidsourceblock'] = 'Mermaid図の原稿';
$string['missingidandcmid'] = 'コースモジュールIDまたはLessonMarkインスタンスIDが必要です。';
$string['missingpreviewcontext'] = 'プレビューにはコースまたはLessonMark教材が必要です。';
$string['modulename'] = 'LessonMark';
$string['modulename_help'] = 'Markdownを正本として保持しながら教材を作成・公開します。';
$string['modulenameplural'] = 'LessonMark教材';
$string['noinstances'] = 'このコースにはLessonMark教材がありません。';
$string['pdfanswerheading'] = '解答例と解説';
$string['pdfimageomitted'] = '画像は収録されていません';
$string['pdfsavedcontentnote'] = 'Moodleに保存済みの内容から生成しました。ブラウザ内の作業答案は含まず、解答解説は展開して収録しています。';
$string['pluginadministration'] = 'LessonMark管理';
$string['pluginname'] = 'LessonMark';
$string['presentation'] = 'この教材をスライド表示';
$string['presentationexitfullscreen'] = '全画面を終了';
$string['presentationfullscreen'] = '全画面';
$string['presentationnext'] = '次へ';
$string['presentationpage'] = 'ページ';
$string['presentationprevious'] = '前へ';
$string['presentationreturn'] = '教材に戻る';
$string['previewempty'] = 'ここにプレビューが表示されます。';
$string['previewerror'] = 'プレビューを更新できませんでした。';
$string['previewloading'] = 'プレビューを更新しています…';
$string['previewmode'] = 'プレビュー';
$string['previewready'] = 'プレビューを更新しました。';
$string['privacy:metadata'] = 'LessonMarkプラグインは教材内容を保存しますが、個人データは保存しません。';
$string['refreshpreview'] = 'プレビューを更新';
$string['scrollabletable'] = '横スクロール可能な表';
$string['selfcheckchoice'] = '解答を選択してください';
$string['selfcheckclear'] = '自分の解答を消去';
$string['selfchecklocalnote'] = '採点・提出されない作業用解答です。このブラウザ内だけに保存されます。';
$string['selfcheckresponse'] = '自分の解答を入力してください';
$string['selfcheckreveal'] = '解答例と解説を確認する';
$string['tableofcontents'] = '目次';

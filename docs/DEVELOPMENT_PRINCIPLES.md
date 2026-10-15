# Shared development principles for LessonMark and Dual Learning

English | [日本語](DEVELOPMENT_PRINCIPLES.ja.md)

Policy ID: DL-INDEPENDENCE-AND-COOPERATION  
Established: 2026-09-29  
Document revision: 6

## 1. Purpose and scope

**Each plugin provides value independently; together, they make the learning experience better.**

Design changes to `mod_lessonmark`, `format_duallearning`, and `theme_duallearning` against this principle. Passing checks is necessary, but review must also consider independent operation, appropriate responsibility, and concrete benefits from cooperation.

This document guides responsibility and cooperation across the three plugins. It does not replace individual product specifications or claim that every current implementation already meets every principle. Record gaps as issues and improve the areas relevant to a change. If a specification conflicts with these principles, identify the conflict and its intended resolution before implementation.

### Keep learning as the purpose

**Ease of use is not the final goal. Better learning is.**  
**使いやすさは最終目的ではない。よりよい学びにつながることが目的である。**

**Make the tool easy to use; preserve the thinking that learning requires.**  
**道具の操作は分かりやすく。学びに必要な思考は大切にする。**

Reduce uncertainty about where work is saved or what action comes next. Support thinking, explaining in one's own words, recalling, and revisiting mistakes according to the learning objective. Making the tool easier to operate does not mean making every learning task easier. For example, immediate access to an answer may be convenient, but consider whether the design encourages looking at the answer before thinking.

The chain from less operational effort, to more time for teachers to revise materials, to better explanations, practice and feedback, to understanding and retention contains hypotheses at every step. Evaluate usability, opportunities to learn, and learning outcomes separately. Do not equate click counts, completion rates, or authoring time with evidence of understanding or retention. Reducing teachers' workload has value in its own right, but does not alone establish improved learning.

Even without an empirical research setting, keep learning outcomes in view as design hypotheses. When drawing on existing research, check its sources, populations, conditions, and limitations; do not present it as proof of this product's effectiveness. Identify the origins and unknowns of hypotheses derived from judgement or imagination as well. Distinguish research-informed suggestions, design reasoning, functional checks, observations of actual use, and assessments of learning outcomes.

Support teachers in providing explanations, practice, checks, and revision without imposing a single model of a good lesson. Outcomes also depend on content, learners, and teaching practice. When observation is unavailable, record hypotheses and design reviews and continue improving without claiming validation. Do not automatically introduce learner tracking or new data collection for evaluation.

### Require a reason for complexity

**Less complexity for teachers. More clarity for learners.**

**Every feature must justify its complexity.**

Treat these as guiding principles, not claims that the current product has achieved them. Apply them to retaining existing features as well as adding new ones. Name the intended user and task, the unnecessary decisions removed, the new concepts and transitions introduced, and the evidence for the tradeoff. Consider removal, consolidation, clearer presentation and safer defaults before adding another control or setting.

Preserve Moodle's capabilities, permissions, accessibility and access to advanced settings. Reduce operating effort without removing information needed to decide about visibility, submission, assessment or deadlines. Fewer visible controls are not automatically simpler; important actions must remain discoverable.

Evaluate five dimensions: author decisions, learner orientation and next action, suitability for teacher-guided and self-paced work, compatibility with standard Moodle, and consistency of the interaction model. Compare equivalent tasks against standard Topics and Boost. Record the exact version and configuration, and separate code inspection, developer walkthroughs, first-time participant observations and learning-outcome evidence. Do not substitute successful CI or a developer's familiarity for participant evaluation.

For each proposed change, record: user/task → observed problem or hypothesis → value and added complexity → simpler alternatives → evidence and unverified scope → retain, simplify, combine, remove or defer → next acceptance check. This is a development review record, not a form imposed on teachers or learners. Keep the three plugins independently useful.

### Balance guidance with familiar workflows

**Help newcomers find their way. Preserve fluency for experienced users.**

Consider both groups. Reduce uncertainty for first-time users while preserving familiar Moodle terminology, standard editing routes, and direct access to advanced functions. Guidance should be available when needed and easy to skip; avoid forcing experienced users through repeated explanations or extra steps. Do not add separate experience modes unless their value justifies the additional complexity.

Treat each observation as evidence in context. A beginner's difficulty identifies a problem worth investigating, but does not by itself establish a solution for every user. Record the participant's experience, task, device and assistance, and distinguish an individual preference from a recurring problem. Compare proposed changes with the standard workflow and assess both discoverability for newcomers and continuity and efficiency for experienced users. If either group has not been observed, retain that gap explicitly.

Prefer clearer labels, grouping, defaults and removal of redundant controls before introducing another route or concept. Familiarity is not a reason to preserve a demonstrated barrier indefinitely; when a change is justified, record its benefit, the relearning cost, and how standard capabilities remain accessible. Balance the needs of both groups rather than optimising the whole product around one participant.

### The meaning of Dual and the product direction

Dual refers to learning supported by a teacher conducting a class and learning that can proceed through Moodle materials without a teacher explaining them at that moment. These approaches need different arrangements of explanation, practice, checks, questions and feedback. Provide course settings and authoring workflows that make both easier to build. Learning without an immediate teacher does not imply that material authors, assessors or people answering questions are unnecessary.

The Course Format leads this support for the two approaches. It must work with Boost and standard activities rather than relying on a dedicated theme to make its basic design usable. Help beginners find actions from their purposes while preserving experienced users' standard workflows.

The Theme values visual design. It provides value with standard formats and, when combined with the Course Format, understands its structure and makes its intent and priorities visually clearer. Preserve meaningful order, hierarchy and functions while refining colours, typography, spacing, alignment, emphasis and responsive layout.

Evaluate whether learners can understand the structure and proceed without getting lost. In this simulation, do not turn the expression "enjoyable learning" into a measurement of emotion or motivation. Review the Theme's aesthetic order against explicit colour and layout rules, recording reasons for exceptions. Do not treat compliance with these rules as proof of learning outcomes.

Build both approaches from the same substantial teaching material and walk through them as a learner. Compare the Course Format with Boost and standard activities against standard Moodle, then hold the content fixed while comparing the Theme. Evaluate LessonMark independently with a standard format and Boost before testing affected combinations. Distinguish automated content loading from UI authoring and do not substitute developer simulations for first-time participant evidence.

## 2. Independence

- Do not declare any of these three plugins as a mandatory dependency of another. Installing, upgrading, or using basic functionality must not require installing either of the other two.
- Distinguish this mutual independence from necessary platform dependencies, such as Moodle itself or Boost. Declare required dependencies and supported versions accurately.
- LessonMark must support basic authoring, reading, and learning interactions with a standard course format and Boost.
- The course format must support course organisation and learning navigation with standard activities other than LessonMark.
- The theme must work with standard course formats and activities, preserving normal display and interaction when the other two plugins are absent.
- When a cooperating plugin is absent, not selected, or lacks the relevant feature, omit the optional integration or fall back to standard navigation. Basic operations must not produce errors or dead ends.

## 3. Responsibility boundaries

| Plugin | Primary responsibility | Responsibility delegated elsewhere |
| --- | --- | --- |
| LessonMark | Editing, storing, and rendering teaching materials; interactions within lessons; imports and exports that preserve meaning | Course-wide learning structure and site-wide appearance |
| format_duallearning | Supporting course and unit organisation, learning paths appropriate to the learning mode, and navigation between activities | Content processing within activities and site-wide appearance |
| theme_duallearning | Site-wide visual clarity, readability, responsive layout, and visual consistency | Content semantics and storage, and decisions about progress, grades, or learning mode |

This division does not assign all CSS to the theme. Activities and course formats must provide the basic display, mobile usability, keyboard operation, and accessibility of their own controls. The theme can then refine spacing, typography, colours, and overall layout.

Do not implement functional decisions such as permissions, visibility rules, or completion conditions solely through theme display changes or CSS hiding. Moodle and the component responsible for the feature remain authoritative.

## 4. Benefits through optional cooperation

Prefer standard Moodle interfaces, including activity information, URLs, completion information, capabilities, the File API, and output mechanisms. When a dedicated integration interface is necessary, identify its provider and consumer and document its purpose, supported scope, and behaviour when unavailable.

- Check that a feature is available before calling another plugin's classes or functions. Installation alone does not mean that the plugin is selected or available in a particular course.
- Do not directly manipulate another plugin's internal database structures or private implementation. Do not duplicate the authoritative records for teaching materials, progress, or grades.
- For visual integration, prefer stable interfaces such as classes or attributes with provider-defined meaning and purpose. Avoid relying on incidental DOM nesting, lesson names, or specific course IDs.
- When changing an integration interface, check its consumers and combinations with older versions. Basic functionality must not require all three plugins to be upgraded simultaneously.
- Allow continued use of teaching materials through normal operations when a companion is removed or a different theme or format is selected. Explain any data deletion associated with plugin uninstallation separately, with reference to Moodle's standard behaviour.

Describe the benefits of cooperation in terms of user actions: learners can find the next activity, materials become easier to read, or teachers spend less time repeating preparation and instructions. The ability to install all three together is not itself evidence of these benefits.

## 5. Examples of deciding where a change belongs

| Problem or objective | Starting point and responsibility |
| --- | --- |
| Standard Topics section navigation creates excessive blank space on phones | Inspect Moodle's standard output and the theme layout. Changing a course format that is not in use does not resolve this problem |
| LessonMark question images should appear wider as part of the site's presentation policy | Adjust theme spacing and display width while preserving image meaning and lesson data |
| LessonMark needs an image enlargement control | Implement it as a LessonMark feature that works without a dedicated theme |
| PDF export treats the contents list as an answer | Fix LessonMark's content interpretation and export processing |
| Activity navigation produced by the Dual Learning format is difficult to use | Inspect the format's own structure and basic styling; do not rely solely on theme compensation |

Investigate the cause and affected scope before assigning responsibility, even when symptoms look similar. If more than one plugin needs changes, explain why each change belongs there and what it achieves independently.

## 6. Change review and validation

Briefly address the following in the change description:

1. Whose action does this improve, and which action?
2. Why does this plugin own the change?
3. Does basic functionality work without the other two plugins?
4. If plugins cooperate, what improves, and what happens when a companion is absent or older?
5. Which combinations were checked, and what remains unverified?

For changes affecting learning or authoring, also include the following brief record. Do not turn these principles into a mandatory form for teachers.

| Question | What to record |
| --- | --- |
| Which burden is reduced? | Operational uncertainty, duplicate work, or similar effort |
| Which learning behaviour is supported? | Thinking, trying, explaining, recalling, or reflecting |
| What connection is expected? | Hypotheses and assumptions linking reduced burden to learning behaviour and outcomes, without asserting proven effects |
| Could there be adverse effects? | Looking at answers too early, hiding consequential information, or completing tasks only formally |
| What can be checked now? | Distinguish functional checks, design reviews, observations of actual use, and assessments of learning outcomes; retain unverified questions |

The baseline independent configurations are LessonMark with a standard format and Boost; standard activities with the Dual Learning format and Boost; and standard activities with a standard format and the Dual Learning theme. Select the necessary checks from these baselines, affected pairs, and the combination of all three plugins.

When changing an integration interface, always check both the configuration without the companion and the relevant combinations. For display and interaction changes, consider phones and desktop screens, long names, keyboard operation, and actual user permissions. Record the results and unverified scope. Passing CI or looking correct with one theme is not evidence that other combinations work.

Do not require runtime checks indiscriminately for documentation-only changes. Choose validation according to the impact of the change and avoid unnecessary duplication.

## 7. Document placement and maintenance

Each repository contains an English version at `docs/DEVELOPMENT_PRINCIPLES.md` and a Japanese version at `docs/DEVELOPMENT_PRINCIPLES.ja.md`. Copies in the same language must be identical across all three repositories. This makes the principles available from any individual checkout without treating one plugin as superior to the others. Do not add a runtime mechanism to retrieve documentation from another repository.

- When changing the principles, increment the document revision and apply the same change to both languages in all three repositories. If updates cannot be made together, explicitly record the languages and repositories still awaiting the change.
- Both language versions express the same policy; neither takes precedence. If their meanings diverge, reconcile both against the agreed design intent and align their revision numbers.
- Use each README as an entry point and link from existing development and design documents. Keep product-specific details in each repository's specifications.
- A change to these principles does not by itself imply replacing published ZIPs, changing plugin versions, or deploying to AWS.

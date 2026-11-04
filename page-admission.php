<?php
/* Template Name: Admission */
get_header();
?>
<section class="container section admission-page">
    <div class="section-heading"><div class="section-meta">Join us</div><h1><?php the_title(); ?></h1></div>
    <div class="entry-content"><?php while (have_posts()): the_post(); the_content(); endwhile; ?></div>
    <?php
    $admission_status = isset($_GET['admission_status']) && is_scalar($_GET['admission_status']) ? sanitize_key(wp_unslash($_GET['admission_status'])) : '';
    $admission_storage_ready = oni_lana_admissions_storage_is_ready();
    $admission_transport_ready = oni_lana_admissions_transport_is_secure();
    $admission_ready = $admission_storage_ready && $admission_transport_ready;
    $admission_periods = oni_lana_admissions_get_periods();
    $admission_open = ! empty( $admission_periods );
    $admission_countdowns = oni_lana_admissions_get_countdowns();
    $application_id = isset($_GET['application_id']) ? absint($_GET['application_id']) : 0;
    $application_number = isset($_GET['application_number']) && is_scalar($_GET['application_number']) ? sanitize_text_field(wp_unslash($_GET['application_number'])) : '';
    if ('received' === $admission_status) :
    ?>
        <p class="admission-notice admission-notice-success" role="status">Your application has been received. The admissions team will verify the submitted information. Application ID: <strong><?php echo esc_html($application_number); ?></strong></p>
        <form class="admission-download-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="oni_lana_admission_pdf">
            <input type="hidden" name="application_id" value="<?php echo esc_attr((string) $application_id); ?>">
            <input type="hidden" name="admission_token" value="">
            <button class="button" type="submit" disabled>Download Application PDF</button>
            <p class="field-help">Your secure download link is available for this session. Keep the downloaded PDF for your records.</p>
        </form>
    <?php elseif (!$admission_storage_ready) : ?>
        <p class="admission-notice admission-notice-error" role="alert">Online applications are temporarily unavailable because secure storage is not configured. Please contact the admissions office.</p>
    <?php elseif (!$admission_transport_ready) : ?>
        <p class="admission-notice admission-notice-error" role="alert">A secure HTTPS connection is required to submit applications. Please use the secure site address.</p>
    <?php elseif (!$admission_open || 'closed' === $admission_status) : ?>
        <?php if ( isset( $admission_countdowns['opening'] ) ) : ?>
            <p class="admission-notice admission-countdown-notice" role="status">
                Online applications for <?php echo esc_html( $admission_countdowns['opening']['label'] ); ?> open in
                <strong data-admission-countdown="<?php echo esc_attr( (string) $admission_countdowns['opening']['timestamp'] ); ?>">Calculating...</strong>
                <span class="screen-reader-text">The countdown uses the school's configured timezone.</span>
            </p>
        <?php else : ?>
            <p class="admission-notice" role="status">Online admissions are currently closed. Please check the admissions page again during the published application period.</p>
        <?php endif; ?>
    <?php elseif ('error' === $admission_status) : ?>
        <p class="admission-notice admission-notice-error" role="alert">Your application could not be submitted. Please check all required fields and documents, then try again.</p>
    <?php else : ?>
        <p class="admission-notice" role="status">Fields marked required must be completed. Upload PDF or JPEG scans up to 200 KB each. Passport photograph and signature must be JPEG images to appear on your application PDF. Your application and documents are encrypted and accessible only to authorized admissions staff.</p>
        <?php if ( isset( $admission_countdowns['closing'] ) ) : ?>
            <p class="admission-notice admission-countdown-notice" role="status">
                Online applications for <?php echo esc_html( $admission_countdowns['closing']['label'] ); ?> close in
                <strong data-admission-countdown="<?php echo esc_attr( (string) $admission_countdowns['closing']['timestamp'] ); ?>">Calculating...</strong>
                <span class="screen-reader-text">The closing countdown uses the school's configured timezone.</span>
            </p>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ('received' !== $admission_status && 'closed' !== $admission_status && $admission_open) : ?>
    <form class="admission-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data" aria-label="Admission application form">
        <input type="hidden" name="action" value="oni_lana_admission_submit">
        <?php wp_nonce_field('oni_lana_admission_submit', 'oni_lana_admission_nonce'); ?>
        <div class="admission-honeypot" aria-hidden="true">
            <label>Leave this field empty<input type="text" name="admission_website" tabindex="-1" autocomplete="off"></label>
        </div>
        <fieldset class="admission-section">
            <legend>1. Applicant (Student) Details <span class="required-note">Required</span></legend>
            <div class="admission-fields">
                <label class="admission-field">Academic Year
                    <select name="academic_year" required>
                        <?php foreach ( $admission_periods as $year => $period ) : ?>
                            <option value="<?php echo esc_attr( (string) $year ); ?>"><?php echo esc_html( $period['label'] . ' (' . $period['start'] . ' to ' . $period['end'] . ')' ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="admission-field admission-field-full">Full Name <span class="field-help">(As shown on the Class 10 mark sheet)</span>
                    <input type="text" name="student_name" autocomplete="name" maxlength="150" required>
                </label>
                <label class="admission-field">Father's Name
                    <input type="text" name="father_name" maxlength="150" required>
                </label>
                <label class="admission-field">Father's Occupation
                    <input type="text" name="father_occupation" maxlength="120" required>
                </label>
                <label class="admission-field">Father's Mobile Number
                    <input type="tel" name="father_mobile" autocomplete="tel" required>
                </label>
                <label class="admission-field">Mother's Name
                    <input type="text" name="mother_name" maxlength="150" required>
                </label>
                <label class="admission-field">Mother's Occupation
                    <input type="text" name="mother_occupation" maxlength="120" required>
                </label>
                <label class="admission-field">Date of Birth <span class="field-help">(DD/MM/YYYY)</span>
                    <input type="date" name="date_of_birth" autocomplete="bday" required>
                </label>
                <label class="admission-field">Gender
                    <select name="gender" required>
                        <option value="">Select gender</option>
                        <option>Female</option>
                        <option>Male</option>
                        <option>Other</option>
                        <option>Prefer not to say</option>
                    </select>
                </label>
                <label class="admission-field">Nationality
                    <input type="text" name="nationality" value="Indian" maxlength="80" required>
                </label>
                <label class="admission-field">Religion
                    <input type="text" name="religion" maxlength="80" required>
                </label>
                <label class="admission-field">Blood Group
                    <select name="blood_group" required>
                        <option value="">Select blood group</option>
                        <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
                        <option>AB+</option><option>AB-</option><option>O+</option><option>O-</option>
                        <option>Unknown</option>
                    </select>
                </label>
                <label class="admission-field">Category / Reservation
                    <select name="category" id="admission-category" required>
                        <option value="">Select category</option>
                        <option value="General">General</option>
                        <option value="SC">SC</option>
                        <option value="ST">ST</option>
                        <option value="OBC">OBC</option>
                        <option value="EWS">EWS</option>
                    </select>
                </label>
                <label class="admission-field">Aadhaar Card Number
                    <input type="text" name="aadhaar_number" inputmode="numeric" autocomplete="off" maxlength="12" pattern="[0-9]{12}" required>
                </label>
                <label class="admission-field">Permanent Education Number
                    <input type="text" name="permanent_education_number" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" required>
                </label>
                <label class="admission-field">Student Mobile Number
                    <input type="tel" name="student_mobile" inputmode="numeric" autocomplete="tel" maxlength="10" pattern="[0-9]{10}" required>
                </label>
                <label class="admission-field">Parent Mobile Number
                    <input type="tel" name="parent_mobile" inputmode="numeric" autocomplete="tel" maxlength="10" pattern="[0-9]{10}" required>
                </label>
                <label class="admission-field">Email Address
                    <input type="email" name="email" inputmode="email" autocomplete="email" maxlength="160" required>
                    <span class="field-help">Enter a valid email address, such as name@example.com.</span>
                </label>
                <label class="admission-field admission-field-full">Permanent Residential Address
                    <textarea name="permanent_address" rows="3" maxlength="300" autocomplete="street-address" required></textarea>
                </label>
            </div>
        </fieldset>

        <fieldset class="admission-section">
            <legend>2. Academic Details (Class 10) <span class="required-note">Required</span></legend>
            <div class="admission-fields">
                <label class="admission-field">Board Name
                    <select name="board_name" required>
                        <option value="">Select board</option>
                        <option>CBSE</option><option>ICSE</option><option>State Board</option><option>Other</option>
                    </select>
                </label>
                <label class="admission-field">Class 10 Roll Number / Seat Number
                    <input type="text" name="class_10_roll_number" maxlength="60" required>
                </label>
                <label class="admission-field">Year of Passing
                    <input type="number" name="passing_year" min="1990" max="<?php echo esc_attr(wp_date('Y')); ?>" required>
                </label>
                <label class="admission-field">Last Attended School Name
                    <input type="text" name="last_school_name" maxlength="200" required>
                </label>
                <label class="admission-field admission-field-full">Last Attended School Address
                    <textarea name="last_school_address" rows="3" maxlength="300" required></textarea>
                </label>
                <label class="admission-field">Total Marks
                    <input type="number" name="total_marks" id="admission-total-marks" min="0.01" step="any">
                </label>
                <label class="admission-field">Marks Obtained
                    <input type="number" name="marks_obtained" id="admission-marks-obtained" min="0" step="any">
                </label>
                <label class="admission-field">Total Percentage
                    <input type="text" name="percentage" id="admission-percentage" placeholder="Calculated when marks are entered" readonly aria-live="polite">
                </label>
                <label class="admission-field">CGPA
                    <input type="number" name="cgpa" id="admission-cgpa" min="0" max="10" step="0.01">
                </label>
                <p class="admission-field admission-field-full field-help">Enter Total Marks and Marks Obtained to calculate a percentage, or enter CGPA. At least one option is required.</p>
            </div>
        </fieldset>

        <fieldset class="admission-section">
            <legend>3. Stream &amp; Subject Selections <span class="required-note">Required</span></legend>
            <div class="admission-fields">
                <label class="admission-field">Desired Stream
                    <select name="stream" id="admission-stream" required>
                        <option value="">Select stream</option>
                        <option value="arts">Arts</option>
                        <option value="science">Science</option>
                        <option value="commerce">Commerce</option>
                    </select>
                </label>
                <label class="admission-field">Subject Combination
                    <select name="subject_combination" id="admission-subject-combination" required disabled>
                        <option value="">Select a stream first</option>
                    </select>
                </label>
                <p class="admission-field admission-field-full field-help">Core English is included in every combination. Choose the combination that matches your intended elective subjects.</p>
            </div>
        </fieldset>

        <fieldset class="admission-section">
            <legend>4. Extracurriculars &amp; Quotas <span class="field-help">(Optional)</span></legend>
            <div class="admission-fields">
                <label class="admission-field">Sports (SGFI, National, or School level)
                    <input type="text" name="sports" maxlength="200">
                </label>
                <label class="admission-field">NCC / Scouts &amp; Guides
                    <input type="text" name="ncc_scouts_guides" maxlength="200">
                </label>
                <label class="admission-field admission-field-full">Management, Linguistic Minority, or In-House Quotas
                    <input type="text" name="other_quotas" maxlength="300">
                </label>
            </div>
        </fieldset>

        <fieldset class="admission-section">
            <legend>5. Document Uploads <span class="field-help">(Scanned copies in PDF or JPEG)</span> <span class="required-note">Required where applicable</span></legend>
            <div class="admission-fields admission-upload-fields">
                <label class="admission-field">Class 10 Mark Sheet
                    <input type="file" name="class_10_marksheet" accept=".pdf,.jpg,.jpeg,application/pdf,image/jpeg" required>
                </label>
                <label class="admission-field">Class 10 Provisional Certificate
                    <input type="file" name="class_10_provisional" accept=".pdf,.jpg,.jpeg,application/pdf,image/jpeg" required>
                </label>
                <label class="admission-field">School Leaving / Transfer Certificate (TC)
                    <input type="file" name="transfer_certificate" accept=".pdf,.jpg,.jpeg,application/pdf,image/jpeg" required>
                </label>
                <label class="admission-field">Are you applying from another board?
                    <select name="other_board" id="admission-other-board" required>
                        <option value="">Select an option</option>
                        <option value="no">No</option>
                        <option value="yes">Yes</option>
                    </select>
                </label>
                <label class="admission-field" id="admission-migration-field" hidden>Migration Certificate
                    <input type="file" name="migration_certificate" accept=".pdf,.jpg,.jpeg,application/pdf,image/jpeg" disabled>
                    <span class="field-help">Required if applying from another board.</span>
                </label>
                <label class="admission-field" id="admission-category-certificate-field" hidden>Category / Disability Certificate
                    <input type="file" name="category_certificate" accept=".pdf,.jpg,.jpeg,application/pdf,image/jpeg" disabled>
                    <span class="field-help">Required when claiming a reservation category.</span>
                </label>
                <label class="admission-field">Passport-size Photograph
                    <input type="file" name="student_photograph" accept=".jpg,.jpeg,image/jpeg" required>
                </label>
                <label class="admission-field">Student / Parent Signature
                    <input type="file" name="student_parent_signature" accept=".jpg,.jpeg,image/jpeg" required>
                </label>
            </div>
        </fieldset>
        <button class="button admission-submit" type="submit" <?php disabled( ! $admission_ready ); ?>><?php echo $admission_ready ? 'Submit Application' : 'Online submission unavailable'; ?></button>
    </form>
    <?php endif; ?>
</section>
<?php get_footer(); ?>

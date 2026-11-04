<?php
/**
 * Secure admission application storage and staff verification screens.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ONI_LANA_ADMISSION_CAP', 'manage_admission_applications' );
define( 'ONI_LANA_ADMISSION_DB_VERSION', '2' );

function oni_lana_admissions_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'ol_admission_applications';
}

function oni_lana_admissions_documents_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'ol_admission_documents';
}

function oni_lana_admissions_install() {
    global $wpdb;

    if ( get_option( 'oni_lana_admission_db_version' ) !== ONI_LANA_ADMISSION_DB_VERSION ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $applications    = oni_lana_admissions_table_name();
        $documents       = oni_lana_admissions_documents_table_name();

        dbDelta(
            "CREATE TABLE {$applications} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                status varchar(20) NOT NULL DEFAULT 'received',
                submitted_at datetime NOT NULL,
                application_number varchar(40) NOT NULL DEFAULT '',
                download_token_hash char(64) NOT NULL DEFAULT '',
                payload longblob NOT NULL,
                payload_iv binary(12) NOT NULL,
                payload_tag binary(16) NOT NULL,
                PRIMARY KEY  (id),
                KEY application_number (application_number),
                KEY download_token_hash (download_token_hash),
                KEY status_submitted (status,submitted_at),
                KEY submitted_at (submitted_at)
            ) {$charset_collate};"
        );
        dbDelta(
            "CREATE TABLE {$documents} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                application_id bigint(20) unsigned NOT NULL,
                document_key varchar(40) NOT NULL,
                content_type varchar(40) NOT NULL,
                file_size bigint(20) unsigned NOT NULL,
                file_data longblob NOT NULL,
                file_iv binary(12) NOT NULL,
                file_tag binary(16) NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY application_document (application_id,document_key),
                KEY application_id (application_id)
            ) {$charset_collate};"
        );
        $applications_ready = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $applications ) ) );
        $documents_ready = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $documents ) ) );
        if ( $applications === $applications_ready && $documents === $documents_ready ) {
            $wpdb->query(
                "UPDATE {$applications}
                SET application_number = CONCAT('OL-', DATE_FORMAT(submitted_at, '%Y'), '-', LPAD(id, 6, '0'))
                WHERE application_number = ''"
            );
            update_option( 'oni_lana_admission_db_version', ONI_LANA_ADMISSION_DB_VERSION, false );
        } else {
            error_log( 'Oni Lana admissions: application database tables could not be created.' );
        }
    }

    $administrator = get_role( 'administrator' );
    if ( $administrator ) {
        $administrator->add_cap( ONI_LANA_ADMISSION_CAP );
    }

    $officer = get_role( 'admission_officer' );
    if ( ! $officer ) {
        add_role(
            'admission_officer',
            'Admission Officer',
            array(
                'read'                         => true,
                ONI_LANA_ADMISSION_CAP         => true,
            )
        );
    } else {
        $officer->add_cap( ONI_LANA_ADMISSION_CAP );
    }
}
add_action( 'init', 'oni_lana_admissions_install', 5 );

function oni_lana_admissions_storage_is_ready() {
    return function_exists( 'openssl_encrypt' )
        && function_exists( 'openssl_decrypt' )
        && function_exists( 'random_bytes' )
        && ONI_LANA_ADMISSION_DB_VERSION === get_option( 'oni_lana_admission_db_version' );
}

function oni_lana_admissions_transport_is_secure() {
    return is_ssl() || in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
}

function oni_lana_admissions_encryption_key() {
    return hash( 'sha256', wp_salt( 'auth' ) . wp_salt( 'secure_auth' ), true );
}

function oni_lana_admissions_encrypt( $plaintext, $context ) {
    if ( ! function_exists( 'openssl_encrypt' ) || ! function_exists( 'random_bytes' ) ) {
        return new WP_Error( 'admission_encryption_unavailable', 'Secure application storage is unavailable. Please contact the admissions office.' );
    }

    $iv  = random_bytes( 12 );
    $tag = '';
    $ciphertext = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        oni_lana_admissions_encryption_key(),
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        $context,
        16
    );

    if ( false === $ciphertext || 16 !== strlen( $tag ) ) {
        return new WP_Error( 'admission_encryption_failed', 'Secure application storage failed. Please contact the admissions office.' );
    }

    return array(
        'ciphertext' => $ciphertext,
        'iv'         => $iv,
        'tag'        => $tag,
    );
}

function oni_lana_admissions_decrypt( $ciphertext, $iv, $tag, $context ) {
    if ( ! function_exists( 'openssl_decrypt' ) ) {
        return new WP_Error( 'admission_decryption_unavailable', 'OpenSSL is required to read encrypted applications.' );
    }

    $plaintext = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        oni_lana_admissions_encryption_key(),
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        $context
    );

    if ( false === $plaintext ) {
        return new WP_Error( 'admission_decryption_failed', 'This application could not be decrypted. Verify that the WordPress security salts have not changed.' );
    }

    return $plaintext;
}

function oni_lana_admissions_field_labels() {
    return array(
        'student_name'                 => 'Full Name',
        'father_name'                  => "Father's Name",
        'father_occupation'            => "Father's Occupation",
        'father_mobile'                => "Father's Mobile Number",
        'mother_name'                  => "Mother's Name",
        'mother_occupation'            => "Mother's Occupation",
        'date_of_birth'                => 'Date of Birth',
        'gender'                       => 'Gender',
        'nationality'                  => 'Nationality',
        'religion'                     => 'Religion',
        'blood_group'                  => 'Blood Group',
        'category'                     => 'Category / Reservation',
        'aadhaar_number'               => 'Aadhaar Card Number',
        'permanent_education_number'   => 'Permanent Education Number',
        'student_mobile'               => 'Student Mobile Number',
        'parent_mobile'                => 'Parent Mobile Number',
        'email'                        => 'Email Address',
        'permanent_address'            => 'Permanent Residential Address',
        'board_name'                   => 'Board Name',
        'class_10_roll_number'         => 'Class 10 Roll / Seat Number',
        'passing_year'                => 'Year of Passing',
        'last_school_name'             => 'Last Attended School',
        'last_school_address'          => 'Last Attended School Address',
        'total_marks'                  => 'Total Marks',
        'marks_obtained'               => 'Marks Obtained',
        'percentage'                   => 'Percentage',
        'cgpa'                         => 'CGPA',
        'stream'                       => 'Desired Stream',
        'subject_combination'          => 'Subject Combination',
        'sports'                       => 'Sports',
        'ncc_scouts_guides'            => 'NCC / Scouts & Guides',
        'other_quotas'                 => 'Other Quotas',
    );
}

function oni_lana_admissions_subject_combinations() {
    return array(
        'arts-history-physical'    => 'Arts — English, History, Political Science, Sociology, Home Science, Physical Education',
        'arts-economics-physical'  => 'Arts — English, History, Political Science, Sociology, Economics, Physical Education',
        'science-biology-home'     => 'Science — English, Physics, Chemistry, Biology, Home Science, Physical Education',
        'science-biology-maths'    => 'Science — English, Physics, Chemistry, Biology, Mathematics, Physical Education',
        'science-maths-home'       => 'Science — English, Physics, Chemistry, Mathematics, Home Science, Physical Education',
        'commerce-maths'           => 'Commerce — English, Business Studies, Accountancy, Mathematics, Economics, Computer Science',
        'commerce-entrepreneurship'=> 'Commerce — English, Business Studies, Accountancy, Entrepreneurship, Economics, Computer Science',
    );
}

function oni_lana_admissions_document_labels() {
    return array(
        'class_10_marksheet'        => 'Class 10 Mark Sheet',
        'class_10_provisional'      => 'Class 10 Provisional Certificate',
        'transfer_certificate'      => 'School Leaving / Transfer Certificate',
        'migration_certificate'     => 'Migration Certificate',
        'category_certificate'      => 'Category / Disability Certificate',
        'student_photograph'        => 'Passport-size Photograph',
        'student_parent_signature'  => 'Student / Parent Signature',
    );
}

function oni_lana_admissions_read_post( $key, $multiline = false ) {
    if ( ! isset( $_POST[ $key ] ) || ! is_scalar( $_POST[ $key ] ) ) {
        return '';
    }

    $value = wp_unslash( $_POST[ $key ] );
    return $multiline ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
}

function oni_lana_admissions_validate_submission( &$documents ) {
    $fields = oni_lana_admissions_field_labels();
    $data   = array();

    foreach ( $fields as $key => $label ) {
        $data[ $key ] = oni_lana_admissions_read_post(
            $key,
            in_array( $key, array( 'permanent_address', 'last_school_address' ), true )
        );
    }

    $email_input = isset( $_POST['email'] ) && is_scalar( $_POST['email'] )
        ? (string) wp_unslash( $_POST['email'] )
        : '';
    $data['email'] = sanitize_email( $email_input );

    $gender_values = array( 'Female', 'Male', 'Other', 'Prefer not to say' );
    $category_values = array( 'General', 'SC', 'ST', 'OBC', 'EWS' );
    $blood_values = array( 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown' );
    $board_values = array( 'CBSE', 'ICSE', 'State Board', 'Other' );
    $stream_values = array( 'arts', 'science', 'commerce' );
    $combinations = oni_lana_admissions_subject_combinations();
    $errors = array();

    foreach ( $fields as $key => $label ) {
        if ( in_array( $key, array( 'total_marks', 'marks_obtained', 'percentage', 'cgpa', 'sports', 'ncc_scouts_guides', 'other_quotas' ), true ) ) {
            continue;
        }
        if ( '' === $data[ $key ] ) {
            $errors[] = $label . ' is required.';
        }
    }

    $max_lengths = array(
        'student_name'               => 150,
        'father_name'                => 150,
        'father_occupation'          => 120,
        'father_mobile'              => 24,
        'mother_name'                => 150,
        'mother_occupation'          => 120,
        'date_of_birth'              => 10,
        'gender'                     => 32,
        'nationality'                => 80,
        'religion'                   => 80,
        'blood_group'                => 8,
        'category'                   => 12,
        'aadhaar_number'             => 12,
        'permanent_education_number' => 10,
        'student_mobile'             => 10,
        'parent_mobile'              => 10,
        'email'                      => 160,
        'permanent_address'          => 1000,
        'board_name'                 => 40,
        'class_10_roll_number'       => 60,
        'passing_year'               => 4,
        'last_school_name'           => 200,
        'last_school_address'        => 1000,
        'total_marks'                => 20,
        'marks_obtained'             => 20,
        'percentage'                 => 16,
        'cgpa'                       => 8,
        'stream'                     => 12,
        'subject_combination'        => 40,
        'sports'                     => 200,
        'ncc_scouts_guides'          => 200,
        'other_quotas'               => 300,
    );
    foreach ( $max_lengths as $key => $max_length ) {
        if ( strlen( $data[ $key ] ) > $max_length ) {
            $errors[] = $fields[ $key ] . ' is too long.';
        }
    }

    if ( ! is_email( $email_input ) || strlen( $email_input ) > 160 ) {
        $errors[] = 'Enter a valid email address.';
    }
    if ( ! in_array( $data['gender'], $gender_values, true ) ) {
        $errors[] = 'Select a valid gender.';
    }
    if ( ! in_array( $data['category'], $category_values, true ) ) {
        $errors[] = 'Select a valid category.';
    }
    if ( ! in_array( $data['blood_group'], $blood_values, true ) ) {
        $errors[] = 'Select a valid blood group.';
    }
    if ( ! in_array( $data['board_name'], $board_values, true ) ) {
        $errors[] = 'Select a valid board.';
    }
    if ( ! in_array( $data['stream'], $stream_values, true ) ) {
        $errors[] = 'Select a valid stream.';
    }
    $expected_stream = array(
        'arts-history-physical'     => 'arts',
        'arts-economics-physical'   => 'arts',
        'science-biology-home'      => 'science',
        'science-biology-maths'     => 'science',
        'science-maths-home'        => 'science',
        'commerce-maths'            => 'commerce',
        'commerce-entrepreneurship' => 'commerce',
    );
    if ( ! isset( $expected_stream[ $data['subject_combination'] ] ) || $expected_stream[ $data['subject_combination'] ] !== $data['stream'] ) {
        $errors[] = 'Select a valid subject combination for your stream.';
    }

    $date = DateTime::createFromFormat( '!Y-m-d', $data['date_of_birth'] );
    if ( ! $date || $date->format( 'Y-m-d' ) !== $data['date_of_birth'] || $date > new DateTime( 'today' ) ) {
        $errors[] = 'Enter a valid date of birth.';
    }
    if ( ! preg_match( '/^[0-9]{12}$/', $data['aadhaar_number'] ) ) {
        $errors[] = 'Enter a valid 12-digit Aadhaar number.';
    }
    if ( ! preg_match( '/^[0-9]{10}$/', $data['permanent_education_number'] ) ) {
        $errors[] = 'Permanent Education Number must contain exactly 10 digits.';
    }
    foreach ( array( 'student_mobile', 'parent_mobile' ) as $phone_key ) {
        if ( ! preg_match( '/^[0-9]{10}$/', $data[ $phone_key ] ) ) {
            $errors[] = $fields[ $phone_key ] . ' must contain exactly 10 digits.';
        }
    }
    if ( ! preg_match( '/^\+?[0-9][0-9 ()-]{6,19}$/', $data['father_mobile'] ) ) {
        $errors[] = 'Enter a valid mobile number for the father.';
    }
    if ( ! ctype_digit( $data['passing_year'] ) || (int) $data['passing_year'] < 1990 || (int) $data['passing_year'] > (int) wp_date( 'Y' ) ) {
        $errors[] = 'Enter a valid year of passing.';
    }

    $has_cgpa  = '' !== $data['cgpa'];
    $has_marks = '' !== $data['total_marks'] || '' !== $data['marks_obtained'];
    $total     = is_numeric( $data['total_marks'] ) ? (float) $data['total_marks'] : 0;
    $obtained  = is_numeric( $data['marks_obtained'] ) ? (float) $data['marks_obtained'] : -1;
    $valid_marks = $total > 0 && $obtained >= 0 && $obtained <= $total;

    if ( ! $has_cgpa && ! $has_marks ) {
        $errors[] = 'Enter either Class 10 marks to calculate a percentage or a CGPA.';
    }
    if ( $has_cgpa && ( ! is_numeric( $data['cgpa'] ) || (float) $data['cgpa'] < 0 || (float) $data['cgpa'] > 10 ) ) {
        $errors[] = 'Enter a CGPA between 0 and 10.';
    }
    if ( $has_marks && ! $valid_marks ) {
        $errors[] = 'Enter valid marks obtained and total marks.';
    }
    $data['percentage'] = $valid_marks
        ? number_format( ( $obtained / $total ) * 100, 2 ) . '%'
        : '';

    $other_board = oni_lana_admissions_read_post( 'other_board' );
    if ( ! in_array( $other_board, array( 'yes', 'no' ), true ) ) {
        $errors[] = 'Select whether you are applying from another board.';
    }

    $documents = array();
    $document_labels = oni_lana_admissions_document_labels();
    $required_documents = array(
        'class_10_marksheet',
        'class_10_provisional',
        'transfer_certificate',
        'student_photograph',
        'student_parent_signature',
    );
    if ( 'yes' === $other_board ) {
        $required_documents[] = 'migration_certificate';
    }
    if ( in_array( $data['category'], array( 'SC', 'ST', 'OBC', 'EWS' ), true ) ) {
        $required_documents[] = 'category_certificate';
    }

    $allowed_mimes = array(
        'pdf'       => 'application/pdf',
        'jpg|jpeg'  => 'image/jpeg',
    );
    $total_upload_size = 0;
    foreach ( $required_documents as $document_key ) {
        if (
            ! isset( $_FILES[ $document_key ] )
            || ! is_array( $_FILES[ $document_key ] )
            || ! isset( $_FILES[ $document_key ]['error'], $_FILES[ $document_key ]['tmp_name'], $_FILES[ $document_key ]['name'], $_FILES[ $document_key ]['size'] )
            || ! is_scalar( $_FILES[ $document_key ]['error'] )
            || ! is_scalar( $_FILES[ $document_key ]['tmp_name'] )
            || ! is_scalar( $_FILES[ $document_key ]['name'] )
            || ! is_scalar( $_FILES[ $document_key ]['size'] )
        ) {
            $errors[] = $document_labels[ $document_key ] . ' is required.';
            continue;
        }

        $file = $_FILES[ $document_key ];
        if ( UPLOAD_ERR_OK !== (int) $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
            $errors[] = $document_labels[ $document_key ] . ' could not be uploaded.';
            continue;
        }

        $size = (int) $file['size'];
        if ( $size < 1 || $size > 200 * KB_IN_BYTES ) {
            $errors[] = $document_labels[ $document_key ] . ' must be no larger than 200 KB.';
            continue;
        }
        $total_upload_size += $size;
        $file_type = wp_check_filetype_and_ext( $file['tmp_name'], sanitize_file_name( $file['name'] ), $allowed_mimes );
        $image_required = in_array( $document_key, array( 'student_photograph', 'student_parent_signature' ), true );
        if (
            empty( $file_type['type'] )
            || ! in_array( $file_type['type'], array( 'application/pdf', 'image/jpeg' ), true )
            || ( $image_required && 'image/jpeg' !== $file_type['type'] )
        ) {
            if ( $image_required ) {
                $errors[] = $document_labels[ $document_key ] . ' must be a JPEG image so it can appear on the application PDF.';
                continue;
            }
            $errors[] = $document_labels[ $document_key ] . ' must be a PDF or JPEG file.';
            continue;
        }

        $content = file_get_contents( $file['tmp_name'] );
        if ( false === $content ) {
            $errors[] = $document_labels[ $document_key ] . ' could not be read.';
            continue;
        }
        if (
            ( 'application/pdf' === $file_type['type'] && 0 !== strpos( $content, '%PDF-' ) )
            || ( 'image/jpeg' === $file_type['type'] && "\xFF\xD8\xFF" !== substr( $content, 0, 3 ) )
        ) {
            $errors[] = $document_labels[ $document_key ] . ' does not contain a valid PDF or JPEG file.';
            continue;
        }
        if ( 'image/jpeg' === $file_type['type'] && ! @getimagesizefromstring( $content ) ) {
            $errors[] = $document_labels[ $document_key ] . ' must be a readable JPEG image.';
            continue;
        }
        $documents[ $document_key ] = array(
            'label'        => $document_labels[ $document_key ],
            'content_type' => $file_type['type'],
            'size'         => $size,
            'content'      => $content,
        );
    }
    if ( $total_upload_size > 1400 * KB_IN_BYTES ) {
        $errors[] = 'The combined document size must not exceed 1,400 KB.';
    }

    if ( ! empty( $errors ) ) {
        return new WP_Error( 'admission_invalid_submission', implode( ' ', array_unique( $errors ) ) );
    }

    $data['other_board'] = $other_board;
    return $data;
}

function oni_lana_admissions_form_url() {
    $page = get_page_by_path( 'admission' );
    return $page ? get_permalink( $page ) : home_url( '/admission/' );
}

function oni_lana_admissions_get_periods() {
    $periods = get_option( 'oni_lana_admission_periods', array() );
    if ( ! is_array( $periods ) ) {
        return array();
    }

    $today = wp_date( 'Y-m-d' );
    $active = array();
    foreach ( $periods as $year => $period ) {
        if (
            ! preg_match( '/^[0-9]{4}$/', (string) $year )
            || ! is_array( $period )
            || empty( $period['open'] )
            || ! isset( $period['start'], $period['end'] )
            || ! is_string( $period['start'] )
            || ! is_string( $period['end'] )
            || ! preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $period['start'] )
            || ! preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $period['end'] )
            || $period['start'] > $period['end']
            || $today < $period['start']
            || $today > $period['end']
        ) {
            continue;
        }
        $start_date = DateTimeImmutable::createFromFormat( '!Y-m-d', $period['start'], wp_timezone() );
        $end_date = DateTimeImmutable::createFromFormat( '!Y-m-d', $period['end'], wp_timezone() );
        if (
            ! $start_date
            || $start_date->format( 'Y-m-d' ) !== $period['start']
            || ! $end_date
            || $end_date->format( 'Y-m-d' ) !== $period['end']
        ) {
            continue;
        }
        $active[ (string) $year ] = array(
            'label' => $year . '-' . substr( (string) ( (int) $year + 1 ), -2 ),
            'start' => $period['start'],
            'end'   => $period['end'],
        );
    }

    return $active;
}

function oni_lana_admissions_get_countdowns() {
    $periods = get_option( 'oni_lana_admission_periods', array() );
    if ( ! is_array( $periods ) ) {
        return array();
    }

    $now = current_datetime()->getTimestamp();
    $countdowns = array();
    foreach ( $periods as $year => $period ) {
        if (
            ! is_array( $period )
            || empty( $period['open'] )
            || ! isset( $period['start'], $period['end'] )
            || ! is_string( $period['start'] )
            || ! is_string( $period['end'] )
            || ! preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $period['start'] )
            || ! preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $period['end'] )
        ) {
            continue;
        }

        $start_date = DateTimeImmutable::createFromFormat( '!Y-m-d', $period['start'], wp_timezone() );
        $end_date = DateTimeImmutable::createFromFormat( '!Y-m-d', $period['end'], wp_timezone() );
        if (
            ! $start_date
            || $start_date->format( 'Y-m-d' ) !== $period['start']
            || ! $end_date
            || $end_date->format( 'Y-m-d' ) !== $period['end']
            || $period['start'] > $period['end']
        ) {
            continue;
        }

        $start_at = $start_date->getTimestamp();
        $closing_date_at = $end_date->getTimestamp();
        $end_at = $end_date->setTime( 23, 59, 59 )->getTimestamp();
        $opens_in = $start_at - $now;
        $closes_in = $closing_date_at - $now;
        $academic_year = $year . '-' . substr( (string) ( (int) $year + 1 ), -2 );

        if ( $opens_in > 0 && $opens_in <= 2 * DAY_IN_SECONDS && ( ! isset( $countdowns['opening'] ) || $start_at < $countdowns['opening']['timestamp'] ) ) {
            $countdowns['opening'] = array(
                'timestamp' => $start_at,
                'label'     => $academic_year,
            );
        }

        if ( $now >= $start_at && $now <= $end_at && $closes_in >= 0 && $closes_in <= 5 * DAY_IN_SECONDS && ( ! isset( $countdowns['closing'] ) || $end_at < $countdowns['closing']['timestamp'] ) ) {
            $countdowns['closing'] = array(
                'timestamp' => $end_at,
                'label'     => $academic_year,
            );
        }
    }

    return $countdowns;
}

function oni_lana_admissions_redirect( $status ) {
    wp_safe_redirect( add_query_arg( 'admission_status', $status, oni_lana_admissions_form_url() ) );
    exit;
}

function oni_lana_admissions_redirect_received( $application_id, $application_number, $download_token ) {
    $url = add_query_arg(
        array(
            'admission_status' => 'received',
            'application_id' => (int) $application_id,
            'application_number' => $application_number,
        ),
        oni_lana_admissions_form_url()
    );
    wp_safe_redirect( $url . '#admission_token=' . rawurlencode( $download_token ) );
    exit;
}

function oni_lana_admissions_submit() {
    global $wpdb;

    if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
        oni_lana_admissions_redirect( 'error' );
    }
    if ( ! isset( $_POST['oni_lana_admission_nonce'] ) || ! is_scalar( $_POST['oni_lana_admission_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['oni_lana_admission_nonce'] ) ), 'oni_lana_admission_submit' ) ) {
        oni_lana_admissions_redirect( 'error' );
    }
    if ( ! empty( $_POST['admission_website'] ) ) {
        oni_lana_admissions_redirect( 'error' );
    }
    if ( ! oni_lana_admissions_storage_is_ready() ) {
        error_log( 'Oni Lana admissions: secure storage is not available; application submission was not accepted.' );
        oni_lana_admissions_redirect( 'error' );
    }
    if ( ! oni_lana_admissions_transport_is_secure() ) {
        error_log( 'Oni Lana admissions: an application was not accepted over an insecure connection.' );
        oni_lana_admissions_redirect( 'error' );
    }

    $active_periods = oni_lana_admissions_get_periods();
    $academic_year = isset( $_POST['academic_year'] ) && is_scalar( $_POST['academic_year'] )
        ? sanitize_text_field( wp_unslash( $_POST['academic_year'] ) )
        : '';
    if ( ! isset( $active_periods[ $academic_year ] ) ) {
        oni_lana_admissions_redirect( 'closed' );
    }

    $documents = array();
    $data = oni_lana_admissions_validate_submission( $documents );
    if ( is_wp_error( $data ) ) {
        oni_lana_admissions_redirect( 'error' );
    }
    $data['academic_year'] = $active_periods[ $academic_year ]['label'];

    try {
        $download_token = bin2hex( random_bytes( 32 ) );
    } catch ( Throwable $exception ) {
        error_log( 'Oni Lana admissions: secure download token generation failed.' );
        oni_lana_admissions_redirect( 'error' );
    }

    $json = wp_json_encode( $data );
    if ( false === $json ) {
        oni_lana_admissions_redirect( 'error' );
    }
    $encrypted_payload = oni_lana_admissions_encrypt( $json, 'oni-lana-admissions:payload:v1' );
    if ( is_wp_error( $encrypted_payload ) ) {
        oni_lana_admissions_redirect( 'error' );
    }

    $encrypted_documents = array();
    foreach ( $documents as $document_key => $document ) {
        $encrypted = oni_lana_admissions_encrypt( $document['content'], 'oni-lana-admissions:document:' . $document_key . ':v1' );
        if ( is_wp_error( $encrypted ) ) {
            oni_lana_admissions_redirect( 'error' );
        }
        $encrypted_documents[ $document_key ] = array_merge( $document, $encrypted );
    }

    $applications_table = oni_lana_admissions_table_name();
    $documents_table    = oni_lana_admissions_documents_table_name();
    if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
        oni_lana_admissions_redirect( 'error' );
    }

    $inserted = $wpdb->insert(
        $applications_table,
        array(
            'status'       => 'received',
            'submitted_at' => current_time( 'mysql', true ),
            'payload'      => $encrypted_payload['ciphertext'],
            'payload_iv'   => $encrypted_payload['iv'],
            'payload_tag'  => $encrypted_payload['tag'],
        ),
        array( '%s', '%s', '%s', '%s', '%s' )
    );

    if ( false === $inserted ) {
        $wpdb->query( 'ROLLBACK' );
        oni_lana_admissions_redirect( 'error' );
    }

    $application_id = (int) $wpdb->insert_id;
    $application_number = sprintf( 'OL-%s-%06d', wp_date( 'Y' ), $application_id );
    $data['application_number'] = $application_number;
    $json = wp_json_encode( $data );
    if ( false === $json ) {
        $wpdb->query( 'ROLLBACK' );
        oni_lana_admissions_redirect( 'error' );
    }
    $encrypted_payload = oni_lana_admissions_encrypt( $json, 'oni-lana-admissions:payload:v1' );
    if ( is_wp_error( $encrypted_payload ) ) {
        $wpdb->query( 'ROLLBACK' );
        oni_lana_admissions_redirect( 'error' );
    }
    $updated = $wpdb->update(
        $applications_table,
        array(
            'application_number' => $application_number,
            'download_token_hash' => hash( 'sha256', $download_token ),
            'payload' => $encrypted_payload['ciphertext'],
            'payload_iv' => $encrypted_payload['iv'],
            'payload_tag' => $encrypted_payload['tag'],
        ),
        array( 'id' => $application_id ),
        array( '%s', '%s', '%s', '%s', '%s' ),
        array( '%d' )
    );
    if ( false === $updated ) {
        $wpdb->query( 'ROLLBACK' );
        oni_lana_admissions_redirect( 'error' );
    }

    foreach ( $encrypted_documents as $document_key => $document ) {
        $saved = $wpdb->insert(
            $documents_table,
            array(
                'application_id' => $application_id,
                'document_key'   => $document_key,
                'content_type'   => $document['content_type'],
                'file_size'      => $document['size'],
                'file_data'      => $document['ciphertext'],
                'file_iv'        => $document['iv'],
                'file_tag'       => $document['tag'],
            ),
            array( '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
        );
        if ( false === $saved ) {
            $wpdb->query( 'ROLLBACK' );
            oni_lana_admissions_redirect( 'error' );
        }
    }

    if ( false === $wpdb->query( 'COMMIT' ) ) {
        $wpdb->query( 'ROLLBACK' );
        oni_lana_admissions_redirect( 'error' );
    }

    oni_lana_admissions_redirect_received( $application_id, $application_number, $download_token );
}
add_action( 'admin_post_nopriv_oni_lana_admission_submit', 'oni_lana_admissions_submit' );
add_action( 'admin_post_oni_lana_admission_submit', 'oni_lana_admissions_submit' );

function oni_lana_admissions_pdf_text( $text, $size, $x, $y, &$commands, $bold = false ) {
    $text = (string) $text;
    if ( function_exists( 'iconv' ) ) {
        $converted = iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $text );
        if ( false !== $converted ) {
            $text = $converted;
        }
    }
    $text = preg_replace( '/[^\x20-\x7E]/', '', $text );
    $text = str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $text );
    $font = $bold ? '/F2' : '/F1';
    $commands[] = sprintf( "BT %s %.1F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n", $font, $size, $x, $y, $text );
}

function oni_lana_admissions_pdf_start_page( $application_number, $submitted_at, &$table_y ) {
    $commands = array(
        "0.10 0.25 0.45 RG 0.10 0.25 0.45 rg 1.2 w\n",
    );
    $institute_name = trim( wp_strip_all_tags( get_theme_mod( 'ol_college_name', get_bloginfo( 'name' ) ) ) );
    $institute_name = $institute_name ? $institute_name : get_bloginfo( 'name' );
    $address = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( get_theme_mod( 'ol_address', '' ) ) ) );
    $address_lines = $address ? explode( "\n", wordwrap( $address, 100, "\n", true ) ) : array();
    $header_offset = max( 0, count( $address_lines ) - 1 ) * 9;
    oni_lana_admissions_pdf_text( $institute_name, 14, 38, 812, $commands, true );
    foreach ( $address_lines as $index => $line ) {
        oni_lana_admissions_pdf_text( $line, 8, 38, 798 - ( $index * 9 ), $commands );
    }
    oni_lana_admissions_pdf_text( 'ADMISSION APPLICATION', 12, 38, 768 - $header_offset, $commands, true );
    $commands[] = sprintf( "0.10 0.25 0.45 RG 38 %.2F m 557 %.2F l S\n", 756 - $header_offset, 756 - $header_offset );
    oni_lana_admissions_pdf_text( 'Application ID: ' . $application_number, 9, 38, 741 - $header_offset, $commands );
    oni_lana_admissions_pdf_text( 'Received: ' . $submitted_at, 8, 38, 727 - $header_offset, $commands );
    $table_y = 704 - $header_offset;
    return $commands;
}

function oni_lana_admissions_pdf_add_image( $image_key, $image, $page_index, $x, $y, $box_width, $box_height, &$image_objects, &$commands ) {
    $info = @getimagesizefromstring( $image );
    if ( ! $info || 'image/jpeg' !== $info['mime'] || empty( $info['channels'] ) || ! in_array( (int) $info['channels'], array( 1, 3, 4 ), true ) ) {
        return false;
    }

    $width = (int) $info[0];
    $height = (int) $info[1];
    if ( $width < 1 || $height < 1 ) {
        return false;
    }

    $scale = min( $box_width / $width, $box_height / $height );
    $draw_width = $width * $scale;
    $draw_height = $height * $scale;
    $draw_x = $x + ( $box_width - $draw_width ) / 2;
    $draw_y = $y + ( $box_height - $draw_height ) / 2;
    $name = 'Im' . ( count( $image_objects ) + 1 );
    $color_space = 1 === (int) $info['channels'] ? '/DeviceGray' : ( 4 === (int) $info['channels'] ? '/DeviceCMYK' : '/DeviceRGB' );
    $image_objects[] = array(
        'name' => $name,
        'page' => $page_index,
        'width' => $width,
        'height' => $height,
        'color_space' => $color_space,
        'data' => $image,
    );
    $commands[] = sprintf( "q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q\n", $draw_width, $draw_height, $draw_x, $draw_y, $name );

    return true;
}

function oni_lana_admissions_build_pdf( $application_number, $submitted_at, $application, $images ) {
    $table_y = 704;
    $pages = array( oni_lana_admissions_pdf_start_page( $application_number, $submitted_at, $table_y ) );
    $page_index = 0;
    $y = $table_y;
    $image_objects = array();
    $valid_images = array();
    foreach ( array( 'student_photograph', 'student_parent_signature' ) as $key ) {
        if ( empty( $images[ $key ] ) ) {
            continue;
        }
        $info = @getimagesizefromstring( $images[ $key ] );
        if ( $info && 'image/jpeg' === $info['mime'] && ! empty( $info['channels'] ) && in_array( (int) $info['channels'], array( 1, 3, 4 ), true ) ) {
            $valid_images[ $key ] = $images[ $key ];
        }
    }

    $field_labels = array( 'academic_year' => 'Academic Year' ) + oni_lana_admissions_field_labels();
    $field_labels['other_board'] = 'Applying from another board?';
    foreach ( $field_labels as $key => $label ) {
        $value = isset( $application[ $key ] ) ? trim( (string) $application[ $key ] ) : '';
        if ( '' === $value ) {
            $value = 'Not provided';
        }
        if ( 'stream' === $key ) {
            $value = ucfirst( $value );
        }
        if ( 'subject_combination' === $key ) {
            $combinations = oni_lana_admissions_subject_combinations();
            $value = isset( $combinations[ $value ] ) ? $combinations[ $value ] : $value;
        }
        if ( 'other_board' === $key ) {
            $value = 'yes' === $value ? 'Yes' : ( 'no' === $value ? 'No' : $value );
        }
        $value = preg_replace( '/\s+/', ' ', $value );
        $is_name_row = 'student_name' === $key;
        $label_lines = explode( "\n", wordwrap( $label, 25, "\n", true ) );
        $value_lines = explode( "\n", wordwrap( $value, $is_name_row ? 25 : 55, "\n", true ) );
        $line_count = max( count( $label_lines ), count( $value_lines ) );
        $row_height = $is_name_row ? max( 88, 10 + ( $line_count * 9 ) ) : max( 22, 10 + ( $line_count * 9 ) );
        if ( $y - $row_height < 65 ) {
            $pages[] = oni_lana_admissions_pdf_start_page( $application_number, $submitted_at, $table_y );
            $page_index = count( $pages ) - 1;
            $y = $table_y;
        }
        $bottom = $y - $row_height;
        if ( $is_name_row ) {
            $pages[ $page_index ][] = sprintf( "0.72 G 38 %.2F 519 %.2F re S 203 %.2F m 203 %.2F l S 375 %.2F m 375 %.2F l S 446 %.2F m 446 %.2F l S 0 G\n", $bottom, $row_height, $bottom, $y, $bottom, $y, $bottom, $y );
        } else {
            $pages[ $page_index ][] = sprintf( "0.72 G 38 %.2F 519 %.2F re S 203 %.2F m 203 %.2F l S 0 G\n", $bottom, $row_height, $bottom, $y );
        }
        foreach ( $label_lines as $line_number => $line ) {
            oni_lana_admissions_pdf_text( $line, 7, 46, $y - 13 - ( $line_number * 9 ), $pages[ $page_index ], true );
        }
        foreach ( $value_lines as $line_number => $line ) {
            oni_lana_admissions_pdf_text( $line, 7.5, 211, $y - 13 - ( $line_number * 9 ), $pages[ $page_index ] );
        }
        if ( $is_name_row ) {
            oni_lana_admissions_pdf_text( 'PHOTO', 6.5, 382, $y - 12, $pages[ $page_index ], true );
            oni_lana_admissions_pdf_text( 'SIGNATURE', 6.5, 453, $y - 12, $pages[ $page_index ], true );
            if ( isset( $valid_images['student_photograph'] ) ) {
                oni_lana_admissions_pdf_add_image( 'student_photograph', $valid_images['student_photograph'], $page_index, 382, $bottom + 7, 57, 57, $image_objects, $pages[ $page_index ] );
            }
            if ( isset( $valid_images['student_parent_signature'] ) ) {
                oni_lana_admissions_pdf_add_image( 'student_parent_signature', $valid_images['student_parent_signature'], $page_index, 452, $bottom + 7, 96, 57, $image_objects, $pages[ $page_index ] );
            }
        }
        $y = $bottom;
    }

    $page_count = count( $pages );
    foreach ( $pages as $index => &$page_commands ) {
        $page_commands[] = "0.75 G 38 47 m 557 47 l S 0 G\n";
        oni_lana_admissions_pdf_text( 'CONFIDENTIAL - contains personal information including Aadhaar.', 6.5, 38, 34, $page_commands );
        oni_lana_admissions_pdf_text( 'Page ' . ( $index + 1 ) . ' of ' . $page_count, 7, 510, 34, $page_commands );
    }
    unset( $page_commands );

    $objects = array( 1 => '<< /Type /Catalog /Pages 2 0 R >>' );
    $font_id = 3 + ( 2 * $page_count );
    $next_image_id = $font_id + 2;
    $kids = array();
    foreach ( $pages as $index => $page_commands ) {
        $page_id = 3 + ( 2 * $index );
        $content_id = $page_id + 1;
        $kids[] = $page_id . ' 0 R';
        $content = implode( '', $page_commands );
        $xobjects = '';
        foreach ( $image_objects as &$image_object ) {
            if ( $index !== $image_object['page'] ) {
                continue;
            }
            $image_object['id'] = $next_image_id++;
            $xobjects .= '/' . $image_object['name'] . ' ' . $image_object['id'] . ' 0 R ';
            $objects[ $image_object['id'] ] = sprintf(
                '<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace %s /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>' . "\nstream\n",
                $image_object['width'],
                $image_object['height'],
                $image_object['color_space'],
                strlen( $image_object['data'] )
            ) . $image_object['data'] . "\nendstream";
        }
        unset( $image_object );
        $objects[ $page_id ] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . $font_id . ' 0 R /F2 ' . ( $font_id + 1 ) . ' 0 R >> /XObject << ' . $xobjects . '>> >> /Contents ' . $content_id . ' 0 R >>';
        $objects[ $content_id ] = '<< /Length ' . strlen( $content ) . " >>\nstream\n" . $content . 'endstream';
    }
    $objects[2] = '<< /Type /Pages /Kids [' . implode( ' ', $kids ) . '] /Count ' . $page_count . ' >>';
    $objects[ $font_id ] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
    $objects[ $font_id + 1 ] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
    ksort( $objects );

    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = array( 0 );
    foreach ( $objects as $object_id => $object ) {
        $offsets[ $object_id ] = strlen( $pdf );
        $pdf .= $object_id . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xref_offset = strlen( $pdf );
    $object_count = max( array_keys( $objects ) ) + 1;
    $pdf .= "xref\n0 " . $object_count . "\n0000000000 65535 f \n";
    for ( $id = 1; $id < $object_count; $id++ ) {
        $pdf .= sprintf( "%010d 00000 n \n", isset( $offsets[ $id ] ) ? $offsets[ $id ] : 0 );
    }
    $pdf .= "trailer\n<< /Size " . $object_count . " /Root 1 0 R >>\nstartxref\n" . $xref_offset . "\n%%EOF";

    return $pdf;
}

function oni_lana_admissions_download_pdf() {
    if ( ! oni_lana_admissions_transport_is_secure() ) {
        wp_die( esc_html__( 'HTTPS is required to download an application PDF.', 'oni-lana' ) );
    }
    if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
        wp_die( esc_html__( 'Invalid PDF download request.', 'oni-lana' ) );
    }

    $application_id = isset( $_POST['application_id'] ) ? absint( $_POST['application_id'] ) : 0;
    if ( ! $application_id ) {
        wp_die( esc_html__( 'Application not found.', 'oni-lana' ) );
    }

    if ( current_user_can( ONI_LANA_ADMISSION_CAP ) ) {
        $nonce = isset( $_POST['oni_lana_admission_pdf_nonce'] ) && is_scalar( $_POST['oni_lana_admission_pdf_nonce'] )
            ? sanitize_text_field( wp_unslash( $_POST['oni_lana_admission_pdf_nonce'] ) )
            : '';
        if ( ! wp_verify_nonce( $nonce, 'oni_lana_admission_pdf_' . $application_id ) ) {
            wp_die( esc_html__( 'The PDF download security check failed.', 'oni-lana' ) );
        }
    } else {
        $token = isset( $_POST['admission_token'] ) && is_scalar( $_POST['admission_token'] )
            ? sanitize_text_field( wp_unslash( $_POST['admission_token'] ) )
            : '';
        if ( ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
            wp_die( esc_html__( 'This application PDF link is invalid or has expired.', 'oni-lana' ) );
        }
    }

    global $wpdb;
    $applications = oni_lana_admissions_table_name();
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT id, application_number, download_token_hash, submitted_at, payload, payload_iv, payload_tag FROM {$applications} WHERE id = %d", $application_id ) );
    if ( ! $row ) {
        wp_die( esc_html__( 'Application not found.', 'oni-lana' ) );
    }
    if ( ! current_user_can( ONI_LANA_ADMISSION_CAP ) && ( empty( $row->download_token_hash ) || ! hash_equals( $row->download_token_hash, hash( 'sha256', $token ) ) ) ) {
        wp_die( esc_html__( 'This application PDF link is invalid or has expired.', 'oni-lana' ) );
    }

    $plaintext = oni_lana_admissions_decrypt( $row->payload, $row->payload_iv, $row->payload_tag, 'oni-lana-admissions:payload:v1' );
    if ( is_wp_error( $plaintext ) ) {
        wp_die( esc_html( $plaintext->get_error_message() ) );
    }
    $application = json_decode( $plaintext, true );
    if ( ! is_array( $application ) ) {
        wp_die( esc_html__( 'The application data is invalid.', 'oni-lana' ) );
    }

    $documents = oni_lana_admissions_documents_table_name();
    $image_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT document_key, content_type, file_data, file_iv, file_tag FROM {$documents} WHERE application_id = %d AND document_key IN ('student_photograph', 'student_parent_signature')",
        $application_id
    ) );
    $images = array();
    foreach ( $image_rows as $image_row ) {
        if ( 'image/jpeg' !== $image_row->content_type ) {
            continue;
        }
        $image = oni_lana_admissions_decrypt(
            $image_row->file_data,
            $image_row->file_iv,
            $image_row->file_tag,
            'oni-lana-admissions:document:' . $image_row->document_key . ':v1'
        );
        if ( is_wp_error( $image ) ) {
            wp_die( esc_html( $image->get_error_message() ) );
        }
        $images[ $image_row->document_key ] = $image;
    }

    $pdf = oni_lana_admissions_build_pdf(
        $row->application_number,
        mysql2date( get_option( 'date_format' ), $row->submitted_at, true ),
        $application,
        $images
    );
    nocache_headers();
    header( 'X-Content-Type-Options: nosniff' );
    header( 'Referrer-Policy: no-referrer' );
    header( 'Content-Type: application/pdf' );
    header( 'Content-Length: ' . strlen( $pdf ) );
    header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $row->application_number . '.pdf' ) . '"' );
    echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated PDF bytes are returned as a file download.
    exit;
}
add_action( 'admin_post_nopriv_oni_lana_admission_pdf', 'oni_lana_admissions_download_pdf' );
add_action( 'admin_post_oni_lana_admission_pdf', 'oni_lana_admissions_download_pdf' );

function oni_lana_admissions_register_admin() {
    add_menu_page(
        'Admission Applications',
        'Applications',
        ONI_LANA_ADMISSION_CAP,
        'oni-lana-admission-verification',
        'oni_lana_admissions_render_admin_page',
        'dashicons-welcome-learn-more',
        26
    );
    add_submenu_page(
        'oni-lana-admission-verification',
        'Application Verification',
        'Verification',
        ONI_LANA_ADMISSION_CAP,
        'oni-lana-admission-verification',
        'oni_lana_admissions_render_admin_page'
    );
}
add_action( 'admin_menu', 'oni_lana_admissions_register_admin' );

function oni_lana_admissions_dashboard_widget() {
    if ( ! current_user_can( ONI_LANA_ADMISSION_CAP ) ) {
        return;
    }
    wp_add_dashboard_widget(
        'oni_lana_admission_verification',
        'Admission Application Verification',
        'oni_lana_admissions_render_dashboard_widget'
    );
}
add_action( 'wp_dashboard_setup', 'oni_lana_admissions_dashboard_widget' );

function oni_lana_admissions_render_dashboard_widget() {
    global $wpdb;
    if ( ! oni_lana_admissions_storage_is_ready() ) {
        echo '<p>Secure admission storage is not ready. Check the WordPress database tables and OpenSSL configuration before accepting applications.</p>';
        return;
    }
    if ( ! oni_lana_admissions_transport_is_secure() ) {
        echo '<p>HTTPS is required to review admission applications. Open the WordPress dashboard using its secure URL.</p>';
        return;
    }
    $table = oni_lana_admissions_table_name();
    $pending_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'received'" );
    $rows = $wpdb->get_results( "SELECT id, application_number, status, submitted_at, payload, payload_iv, payload_tag FROM {$table} ORDER BY submitted_at DESC LIMIT 5" );
    ?>
    <p><strong><?php echo esc_html( number_format_i18n( $pending_count ) ); ?></strong> application(s) awaiting verification.</p>
    <?php if ( $rows ) : ?>
        <ul>
            <?php foreach ( $rows as $row ) :
                $payload = oni_lana_admissions_decrypt( $row->payload, $row->payload_iv, $row->payload_tag, 'oni-lana-admissions:payload:v1' );
                if ( is_wp_error( $payload ) ) {
                    wp_die( esc_html( $payload->get_error_message() ) );
                }
                $application = json_decode( $payload, true );
                if ( ! is_array( $application ) ) {
                    wp_die( esc_html__( 'An application record is invalid.', 'oni-lana' ) );
                }
                $name = isset( $application['student_name'] ) ? $application['student_name'] : 'Application #' . (int) $row->id;
                $detail_url = add_query_arg(
                    array( 'page' => 'oni-lana-admission-verification', 'application' => (int) $row->id ),
                    admin_url( 'admin.php' )
                );
                ?>
                <li>
                    <a href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html( $row->application_number . ' — ' . $name ); ?></a>
                    — <?php echo esc_html( ucfirst( str_replace( '_', ' ', $row->status ) ) ); ?>
                    <span><?php echo esc_html( mysql2date( get_option( 'date_format' ), $row->submitted_at, true ) ); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p>No applications have been received yet.</p>
    <?php endif; ?>
    <p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=oni-lana-admission-verification' ) ); ?>">Open Verification</a></p>
    <?php
}

function oni_lana_admissions_admin_notice() {
    if ( ! current_user_can( ONI_LANA_ADMISSION_CAP ) || ! isset( $_GET['page'] ) || ! is_scalar( $_GET['page'] ) || 'oni-lana-admission-verification' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
        return;
    }
    if ( isset( $_GET['admission_updated'] ) && is_scalar( $_GET['admission_updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['admission_updated'] ) ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>Application status updated.</p></div>';
    }
    if ( isset( $_GET['admission_deleted'] ) && is_scalar( $_GET['admission_deleted'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['admission_deleted'] ) ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>Application and its documents permanently deleted.</p></div>';
    }
    if ( isset( $_GET['admission_period_saved'] ) && is_scalar( $_GET['admission_period_saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['admission_period_saved'] ) ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>Admission period settings saved.</p></div>';
    }
}
add_action( 'admin_notices', 'oni_lana_admissions_admin_notice' );

function oni_lana_admissions_render_period_settings() {
    $periods = get_option( 'oni_lana_admission_periods', array() );
    if ( ! is_array( $periods ) ) {
        $periods = array();
    }
    $selected_year = isset( $_GET['edit_academic_year'] ) && is_scalar( $_GET['edit_academic_year'] )
        ? sanitize_text_field( wp_unslash( $_GET['edit_academic_year'] ) )
        : '';
    $selected = isset( $periods[ $selected_year ] ) && is_array( $periods[ $selected_year ] )
        ? $periods[ $selected_year ]
        : array();
    ?>
    <h2>Admission Availability</h2>
    <p>Set an academic year, its inclusive opening and closing dates, and whether applications are open. Dates use the WordPress site timezone.</p>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="oni_lana_admission_save_period">
        <?php wp_nonce_field( 'oni_lana_admission_save_period' ); ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="admission-academic-year">Academic year start</label></th>
                    <td><input id="admission-academic-year" name="academic_year_start" type="number" min="2000" max="2099" step="1" value="<?php echo esc_attr( $selected_year ? $selected_year : wp_date( 'Y' ) ); ?>" required> <span class="description">For example, 2026 represents 2026–27.</span></td>
                </tr>
                <tr>
                    <th scope="row"><label for="admission-period-start">Applications open from</label></th>
                    <td><input id="admission-period-start" name="period_start" type="date" value="<?php echo esc_attr( isset( $selected['start'] ) ? $selected['start'] : '' ); ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="admission-period-end">Applications close after</label></th>
                    <td><input id="admission-period-end" name="period_end" type="date" value="<?php echo esc_attr( isset( $selected['end'] ) ? $selected['end'] : '' ); ?>" required> <span class="description">The closing date remains open through the end of that local calendar day.</span></td>
                </tr>
                <tr>
                    <th scope="row">Accept applications</th>
                    <td><label><input name="period_open" type="checkbox" value="1" <?php checked( ! empty( $selected['open'] ) ); ?>> Open this academic year's admission period</label></td>
                </tr>
            </tbody>
        </table>
        <?php submit_button( 'Save Admission Period' ); ?>
    </form>
    <?php if ( $periods ) : ?>
        <h3>Configured academic years</h3>
        <table class="widefat striped" style="max-width:900px">
            <thead><tr><th>Academic year</th><th>Date range</th><th>Availability</th><th></th></tr></thead>
            <tbody>
                <?php foreach ( $periods as $year => $period ) :
                    if ( ! is_array( $period ) ) {
                        continue;
                    }
                    $year_label = $year . '-' . substr( (string) ( (int) $year + 1 ), -2 );
                    $edit_url = add_query_arg(
                        array( 'page' => 'oni-lana-admission-verification', 'edit_academic_year' => (string) $year ),
                        admin_url( 'admin.php' )
                    );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $year_label ); ?></td>
                        <td><?php echo esc_html( ( isset( $period['start'] ) ? $period['start'] : '' ) . ' – ' . ( isset( $period['end'] ) ? $period['end'] : '' ) ); ?></td>
                        <td><?php echo esc_html( ! empty( $period['open'] ) ? 'Open' : 'Closed' ); ?></td>
                        <td><a href="<?php echo esc_url( $edit_url ); ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php
}

function oni_lana_admissions_status_label( $status ) {
    $labels = array(
        'received'    => 'Received',
        'under_review'=> 'Under Review',
        'verified'    => 'Verified',
        'rejected'    => 'Rejected',
    );
    return isset( $labels[ $status ] ) ? $labels[ $status ] : 'Received';
}

function oni_lana_admissions_render_admin_page() {
    if ( ! current_user_can( ONI_LANA_ADMISSION_CAP ) ) {
        wp_die( esc_html__( 'You are not allowed to view admission applications.', 'oni-lana' ) );
    }
    if ( ! oni_lana_admissions_transport_is_secure() ) {
        wp_die( esc_html__( 'HTTPS is required to review admission applications.', 'oni-lana' ) );
    }
    global $wpdb;
    $applications_table = oni_lana_admissions_table_name();
    $documents_table = oni_lana_admissions_documents_table_name();
    $application_id = isset( $_GET['application'] ) ? absint( $_GET['application'] ) : 0;
    echo '<div class="wrap"><h1>Admission Application Verification</h1>';
    oni_lana_admissions_render_period_settings();

    if ( ! oni_lana_admissions_storage_is_ready() ) {
        echo '<p>Secure admission storage is not ready. Check the WordPress database tables and OpenSSL configuration before accepting applications.</p></div>';
        return;
    }

    if ( $application_id ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$applications_table} WHERE id = %d", $application_id ) );
        if ( ! $row ) {
            echo '<p>Application not found.</p></div>';
            return;
        }
        $plaintext = oni_lana_admissions_decrypt( $row->payload, $row->payload_iv, $row->payload_tag, 'oni-lana-admissions:payload:v1' );
        if ( is_wp_error( $plaintext ) ) {
            wp_die( esc_html( $plaintext->get_error_message() ) );
        }
        $application = json_decode( $plaintext, true );
        if ( ! is_array( $application ) ) {
            wp_die( esc_html__( 'The application data is invalid.', 'oni-lana' ) );
        }
        echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=oni-lana-admission-verification' ) ) . '">&larr; All applications</a></p>';
        echo '<h2>Application ' . esc_html( $row->application_number ) . '</h2>';
        echo '<p>Received ' . esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row->submitted_at, true ) ) . ' — Status: <strong>' . esc_html( oni_lana_admissions_status_label( $row->status ) ) . '</strong></p>';
        echo '<h3>Applicant and Academic Details</h3><table class="widefat striped" style="max-width:900px"><tbody>';
        foreach ( oni_lana_admissions_field_labels() as $key => $label ) {
            $value = isset( $application[ $key ] ) ? $application[ $key ] : '';
            if ( 'aadhaar_number' === $key && '' !== $value ) {
                $value = str_repeat( '•', max( 0, strlen( $value ) - 4 ) ) . substr( $value, -4 );
            }
            if ( '' !== (string) $value ) {
                echo '<tr><th style="width:260px">' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( (string) $value ) ) . '</td></tr>';
            }
        }
        if ( ! empty( $application['academic_year'] ) ) {
            echo '<tr><th style="width:260px">Academic Year</th><td>' . esc_html( $application['academic_year'] ) . '</td></tr>';
        }
        echo '</tbody></table>';

        ?>
        <form class="admission-admin-pdf-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="oni_lana_admission_pdf">
            <input type="hidden" name="application_id" value="<?php echo esc_attr( (string) $application_id ); ?>">
            <?php wp_nonce_field( 'oni_lana_admission_pdf_' . $application_id, 'oni_lana_admission_pdf_nonce' ); ?>
            <?php submit_button( 'Download Application PDF', 'secondary', 'submit', false ); ?>
        </form>
        <?php

        $files = $wpdb->get_results( $wpdb->prepare( "SELECT id, document_key, content_type, file_size FROM {$documents_table} WHERE application_id = %d ORDER BY id ASC", $application_id ) );
        echo '<h3>Documents</h3>';
        if ( $files ) {
            echo '<ul>';
            foreach ( $files as $file ) {
                $labels = oni_lana_admissions_document_labels();
                $label = isset( $labels[ $file->document_key ] ) ? $labels[ $file->document_key ] : 'Document';
                $download_url = wp_nonce_url(
                    add_query_arg( array( 'action' => 'oni_lana_admission_download', 'document' => (int) $file->id ), admin_url( 'admin-post.php' ) ),
                    'oni_lana_admission_download_' . (int) $file->id
                );
                echo '<li><a href="' . esc_url( $download_url ) . '">' . esc_html( $label ) . '</a> (' . esc_html( size_format( (int) $file->file_size ) ) . ')</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No documents were stored for this application.</p>';
        }

        $status_url = admin_url( 'admin-post.php' );
        ?>
        <h3>Verification Status</h3>
        <form method="post" action="<?php echo esc_url( $status_url ); ?>">
            <input type="hidden" name="action" value="oni_lana_admission_update_status">
            <input type="hidden" name="application_id" value="<?php echo esc_attr( (string) $application_id ); ?>">
            <?php wp_nonce_field( 'oni_lana_admission_update_status_' . $application_id ); ?>
            <select name="status" aria-label="Application status">
                <?php foreach ( array( 'received', 'under_review', 'verified', 'rejected' ) as $status ) : ?>
                    <option value="<?php echo esc_attr( $status ); ?>" <?php selected( $row->status, $status ); ?>><?php echo esc_html( oni_lana_admissions_status_label( $status ) ); ?></option>
                <?php endforeach; ?>
            </select>
            <?php submit_button( 'Save Status', 'primary', 'submit', false ); ?>
        </form>
        <h3>Delete Application</h3>
        <form method="post" action="<?php echo esc_url( $status_url ); ?>" onsubmit="return confirm('Permanently delete this application and all its stored documents? This cannot be undone.');">
            <input type="hidden" name="action" value="oni_lana_admission_delete">
            <input type="hidden" name="application_id" value="<?php echo esc_attr( (string) $application_id ); ?>">
            <?php wp_nonce_field( 'oni_lana_admission_delete_' . $application_id ); ?>
            <label><input type="checkbox" name="confirm_delete" value="1" required> I understand this permanently deletes the application and its documents.</label>
            <?php submit_button( 'Delete Application Permanently', 'delete', 'submit', false ); ?>
        </form>
        <?php
    } else {
        $page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $per_page = 20;
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$applications_table}" );
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, application_number, status, submitted_at, payload, payload_iv, payload_tag FROM {$applications_table} ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
                $per_page,
                ( $page - 1 ) * $per_page
            )
        );
        if ( $rows ) {
            echo '<table class="widefat striped"><thead><tr><th>Application</th><th>Student</th><th>Stream</th><th>Category</th><th>Received</th><th>Status</th></tr></thead><tbody>';
            foreach ( $rows as $row ) {
                $plaintext = oni_lana_admissions_decrypt( $row->payload, $row->payload_iv, $row->payload_tag, 'oni-lana-admissions:payload:v1' );
                if ( is_wp_error( $plaintext ) ) {
                    wp_die( esc_html( $plaintext->get_error_message() ) );
                }
                $application = json_decode( $plaintext, true );
                if ( ! is_array( $application ) ) {
                    wp_die( esc_html__( 'An application record is invalid.', 'oni-lana' ) );
                }
                $detail_url = add_query_arg(
                    array( 'page' => 'oni-lana-admission-verification', 'application' => (int) $row->id ),
                    admin_url( 'admin.php' )
                );
                $stream = isset( $application['stream'] ) ? ucfirst( $application['stream'] ) : '';
                echo '<tr><td><a href="' . esc_url( $detail_url ) . '">' . esc_html( $row->application_number ) . '</a></td>';
                echo '<td><a href="' . esc_url( $detail_url ) . '">' . esc_html( isset( $application['student_name'] ) ? $application['student_name'] : 'Unknown' ) . '</a></td>';
                echo '<td>' . esc_html( $stream ) . '</td><td>' . esc_html( isset( $application['category'] ) ? $application['category'] : '' ) . '</td>';
                echo '<td>' . esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row->submitted_at, true ) ) . '</td>';
                echo '<td>' . esc_html( oni_lana_admissions_status_label( $row->status ) ) . '</td></tr>';
            }
            echo '</tbody></table>';
            $pages = (int) ceil( $total / $per_page );
            if ( $pages > 1 ) {
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo wp_kses_post( paginate_links( array(
                    'base'      => add_query_arg( array( 'page' => 'oni-lana-admission-verification', 'paged' => '%#%' ), admin_url( 'admin.php' ) ),
                    'format'    => '',
                    'current'   => $page,
                    'total'     => $pages,
                    'type'      => 'plain',
                ) ) );
                echo '</div></div>';
            }
        } else {
            echo '<p>No applications have been received yet.</p>';
        }
    }
    echo '</div>';
}

function oni_lana_admissions_update_status() {
    if ( ! current_user_can( ONI_LANA_ADMISSION_CAP ) ) {
        wp_die( esc_html__( 'You are not allowed to update admission applications.', 'oni-lana' ) );
    }
    if ( ! oni_lana_admissions_transport_is_secure() ) {
        wp_die( esc_html__( 'HTTPS is required to update admission applications.', 'oni-lana' ) );
    }

    $application_id = isset( $_POST['application_id'] ) ? absint( $_POST['application_id'] ) : 0;
    check_admin_referer( 'oni_lana_admission_update_status_' . $application_id );
    $status = isset( $_POST['status'] ) && is_scalar( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
    if ( ! $application_id || ! in_array( $status, array( 'received', 'under_review', 'verified', 'rejected' ), true ) ) {
        wp_die( esc_html__( 'Invalid application status.', 'oni-lana' ) );
    }

    global $wpdb;
    $updated = $wpdb->update(
        oni_lana_admissions_table_name(),
        array( 'status' => $status ),
        array( 'id' => $application_id ),
        array( '%s' ),
        array( '%d' )
    );
    if ( false === $updated ) {
        wp_die( esc_html__( 'The application status could not be updated.', 'oni-lana' ) );
    }
    wp_safe_redirect( add_query_arg(
        array( 'page' => 'oni-lana-admission-verification', 'application' => $application_id, 'admission_updated' => '1' ),
        admin_url( 'admin.php' )
    ) );
    exit;
}
add_action( 'admin_post_oni_lana_admission_update_status', 'oni_lana_admissions_update_status' );

function oni_lana_admissions_save_period() {
    if ( ! current_user_can( ONI_LANA_ADMISSION_CAP ) ) {
        wp_die( esc_html__( 'You are not allowed to manage admission periods.', 'oni-lana' ) );
    }
    if ( ! oni_lana_admissions_transport_is_secure() ) {
        wp_die( esc_html__( 'HTTPS is required to manage admission periods.', 'oni-lana' ) );
    }
    check_admin_referer( 'oni_lana_admission_save_period' );

    $year = isset( $_POST['academic_year_start'] ) ? absint( $_POST['academic_year_start'] ) : 0;
    $start = isset( $_POST['period_start'] ) && is_scalar( $_POST['period_start'] )
        ? sanitize_text_field( wp_unslash( $_POST['period_start'] ) )
        : '';
    $end = isset( $_POST['period_end'] ) && is_scalar( $_POST['period_end'] )
        ? sanitize_text_field( wp_unslash( $_POST['period_end'] ) )
        : '';
    $valid_start = DateTimeImmutable::createFromFormat( '!Y-m-d', $start, wp_timezone() );
    $valid_end = DateTimeImmutable::createFromFormat( '!Y-m-d', $end, wp_timezone() );
    if (
        $year < 2000
        || $year > 2099
        || ! $valid_start
        || $valid_start->format( 'Y-m-d' ) !== $start
        || ! $valid_end
        || $valid_end->format( 'Y-m-d' ) !== $end
        || $start > $end
    ) {
        wp_die( esc_html__( 'Enter a valid academic year and date range.', 'oni-lana' ) );
    }

    $periods = get_option( 'oni_lana_admission_periods', array() );
    if ( ! is_array( $periods ) ) {
        $periods = array();
    }
    $periods[ (string) $year ] = array(
        'start' => $start,
        'end'   => $end,
        'open'  => isset( $_POST['period_open'] ) ? 1 : 0,
    );
    update_option( 'oni_lana_admission_periods', $periods, false );

    wp_safe_redirect( add_query_arg(
        array( 'page' => 'oni-lana-admission-verification', 'admission_period_saved' => '1', 'edit_academic_year' => $year ),
        admin_url( 'admin.php' )
    ) );
    exit;
}
add_action( 'admin_post_oni_lana_admission_save_period', 'oni_lana_admissions_save_period' );

function oni_lana_admissions_delete_application() {
    if ( ! current_user_can( ONI_LANA_ADMISSION_CAP ) ) {
        wp_die( esc_html__( 'You are not allowed to delete admission applications.', 'oni-lana' ) );
    }
    if ( ! oni_lana_admissions_transport_is_secure() ) {
        wp_die( esc_html__( 'HTTPS is required to delete admission applications.', 'oni-lana' ) );
    }

    $application_id = isset( $_POST['application_id'] ) ? absint( $_POST['application_id'] ) : 0;
    check_admin_referer( 'oni_lana_admission_delete_' . $application_id );
    if ( ! $application_id || ! isset( $_POST['confirm_delete'] ) || '1' !== $_POST['confirm_delete'] ) {
        wp_die( esc_html__( 'Confirm the application deletion request.', 'oni-lana' ) );
    }

    global $wpdb;
    $applications = oni_lana_admissions_table_name();
    $documents = oni_lana_admissions_documents_table_name();
    if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$applications} WHERE id = %d", $application_id ) ) ) {
        wp_die( esc_html__( 'Application not found.', 'oni-lana' ) );
    }
    if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
        wp_die( esc_html__( 'The application could not be deleted.', 'oni-lana' ) );
    }
    $deleted_documents = $wpdb->delete( $documents, array( 'application_id' => $application_id ), array( '%d' ) );
    $deleted_application = false === $deleted_documents
        ? false
        : $wpdb->delete( $applications, array( 'id' => $application_id ), array( '%d' ) );
    if ( false === $deleted_documents || 1 !== $deleted_application ) {
        $wpdb->query( 'ROLLBACK' );
        wp_die( esc_html__( 'The application and its documents could not be deleted.', 'oni-lana' ) );
    }
    if ( false === $wpdb->query( 'COMMIT' ) ) {
        $wpdb->query( 'ROLLBACK' );
        wp_die( esc_html__( 'The application could not be deleted.', 'oni-lana' ) );
    }

    wp_safe_redirect( add_query_arg(
        array( 'page' => 'oni-lana-admission-verification', 'admission_deleted' => '1' ),
        admin_url( 'admin.php' )
    ) );
    exit;
}
add_action( 'admin_post_oni_lana_admission_delete', 'oni_lana_admissions_delete_application' );

function oni_lana_admissions_download_document() {
    if ( ! current_user_can( ONI_LANA_ADMISSION_CAP ) ) {
        wp_die( esc_html__( 'You are not allowed to download admission documents.', 'oni-lana' ) );
    }
    if ( ! oni_lana_admissions_transport_is_secure() ) {
        wp_die( esc_html__( 'HTTPS is required to download admission documents.', 'oni-lana' ) );
    }

    $document_id = isset( $_GET['document'] ) ? absint( $_GET['document'] ) : 0;
    check_admin_referer( 'oni_lana_admission_download_' . $document_id );
    if ( ! $document_id ) {
        wp_die( esc_html__( 'Invalid document.', 'oni-lana' ) );
    }

    global $wpdb;
    $document = $wpdb->get_row( $wpdb->prepare(
        'SELECT document_key, content_type, file_size, file_data, file_iv, file_tag FROM ' . oni_lana_admissions_documents_table_name() . ' WHERE id = %d',
        $document_id
    ) );
    if ( ! $document ) {
        wp_die( esc_html__( 'Document not found.', 'oni-lana' ) );
    }

    $content = oni_lana_admissions_decrypt(
        $document->file_data,
        $document->file_iv,
        $document->file_tag,
        'oni-lana-admissions:document:' . $document->document_key . ':v1'
    );
    if ( is_wp_error( $content ) ) {
        wp_die( esc_html( $content->get_error_message() ) );
    }

    $labels = oni_lana_admissions_document_labels();
    $base_name = isset( $labels[ $document->document_key ] ) ? sanitize_file_name( $labels[ $document->document_key ] ) : 'admission-document';
    $extension = 'application/pdf' === $document->content_type ? 'pdf' : 'jpg';
    nocache_headers();
    header( 'X-Content-Type-Options: nosniff' );
    header( 'Content-Type: ' . $document->content_type );
    header( 'Content-Length: ' . strlen( $content ) );
    header( 'Content-Disposition: attachment; filename="' . $base_name . '.' . $extension . '"' );
    echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- decrypted document bytes are returned as a file download.
    exit;
}
add_action( 'admin_post_oni_lana_admission_download', 'oni_lana_admissions_download_document' );

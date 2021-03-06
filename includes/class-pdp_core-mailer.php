<?php

class PDP_Core_Mailer{
	private $admin_emails;

	public function __construct(){
		$this->init();
	}

	private function init(){
		$additional_recipients = explode( ',' , get_option( '_email_recipients' ) );

		$this->admin_emails = array_merge(
			[get_option( 'admin_email' )],
			$additional_recipients
		);

		add_filter( 'wp_mail_content_type', function( $content_type ){
			return 'text/html';
		} );
	}

	private function send_to_admins( $subject, $message, $attachments = array(), $recipients = false ){
		return wp_mail( ( $recipients ) ? $recipients : $this->admin_emails, $subject, $message, '', $attachments );
	}

	private function get_template_base( $title, $content ){
		ob_start();
		pdp_get_template( 'emails/base.php', ['title' => $title,'content' => $content] );
		return ob_get_clean();
	}

	private function get_template_booking( $data, $is_simple = false ){
		$salon_name = PDP_Core_Salon::get_by_id( ( !$is_simple ) ? $data['cart']['salon'] : $data['salon'] )->post_title;
		ob_start();
		pdp_get_template( 'emails/booking/body.php', ['data' => $data, 'salon_name' => $salon_name] );

		if( !$is_simple ){
			echo $this->get_template_cart( $data['cart'], $data['total'] );
		}
		else{
			echo $this->get_template_simple_cart( $data['service'] );
		}

		$template = ob_get_clean();

		return $this->get_template_base( '', $template );
	}

	private function get_template_simple_cart( $service ){
		ob_start();
		pdp_get_template( 'emails/booking/simple-cart.php', ['service' => $service] );
		return ob_get_clean();
	}

	private function get_template_cart( $cart, $total ){
		ob_start();
		pdp_get_template( 'emails/booking/cart.php', ['cart' => $cart, 'total' => $total] );
		return ob_get_clean();
	}

	private function get_template_gift_card( $data ){
		ob_start();
		pdp_get_template( 'emails/gift-card.php', ['data' => $data] );
		$template = ob_get_clean();

		return $this->get_template_base( '', $template );
	}

	private function get_template_school_application( $data ){
		ob_start();
		pdp_get_template( 'emails/school-application.php', ['data' => $data] );
		$template = ob_get_clean();

		return $this->get_template_base( '', $template );
	}

	private function get_template_vacancy_application( $data ){
		$data = $data;
		ob_start();
		pdp_get_template( 'emails/vacancy-application.php', ['data' => $data] );
		$template = ob_get_clean();

		return $this->get_template_base( '', $template );
	}

	public function booking_notification( $data ){
		$recipients = array_merge( $this->admin_emails, pdp_get_salon_recipients( $data['cart']['salon'] ) );

		return $this->send_to_admins( __( 'Новая запись', 'pdp_core' ) , $this->get_template_booking( $data ), array(), $recipients );
	}

	public function simple_booking_notification( $data ){
		$recipients = array_merge( $this->admin_emails, pdp_get_salon_recipients( $data['salon'] ) );

		return $this->send_to_admins( __( 'Заявка', 'pdp_core' ) . " | {$data['page_title']}", $this->get_template_booking( $data, true ), array(), $recipients );
	}

	public function service_booking_notification( $data ){
		$recipients = array_merge( $this->admin_emails, pdp_get_salon_recipients( $data['salon'] ) );

		return $this->send_to_admins( __( 'Заявка', 'pdp_core' ) . " | {$data['page_title']}", $this->get_template_booking( $data, true ), array(), $recipients );
	}

	public function category_booking_notification( $data ){
		return $this->send_to_admins( __( 'Заявка', 'pdp_core' ) . " | {$data['page_title']}", $this->get_template_booking( $data, true ) );
	}

	public function gift_card_notification( $data ){
		return $this->send_to_admins( __( 'Заказ подарочного сертификата', 'pdp_core' ), $this->get_template_gift_card( $data ) );
	}

	public function school_application_notification( $data ){
		return $this->send_to_admins( __( 'Заявка на обучение', 'pdp_core' ), $this->get_template_school_application( $data ) );
	}

	public function vacancy_application_notification( $data, $attachment ){
		return $this->send_to_admins( __( 'Отклик на вакансию', 'pdp_core' ), $this->get_template_vacancy_application( $data ), $attachment );
	}
}
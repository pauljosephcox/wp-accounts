<section class="accounts-section accounts-section-login row">

	<div class="container">

		<div class="wrap">

			<div class="panel">

				<h4 class="title">Login</h4>

				<div class="text accounts-login"><?php wp_login_form( array('redirect' => '/account' ) ); ?></div>

				<div class="text accounts-login-link">

					<a href="/account/lostpassword">Lost password?</a>

					<a href="/account/create">Create an account</a>

				</div>

			</div>

		</div>

	</div>

</section>

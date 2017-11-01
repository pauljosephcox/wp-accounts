<section class="accounts-section accounts-lost-password row darkbg">

	<div class="container">

		<div class="">


			<div class="content">

				<div class="text">

					<form action="/account" method="post"  name="account-creation-form" class="account-creation-form">

						<div class="field field-password">

							<label>New Password</label>

							<input type="password" id="password" name="account[password]" placeholder="Password" value="">

						</div>

						<div class="field field-password-confirm">

							<label>Confirm new password</label>

							<input type="password" name="account[confirmpassword]" placeholder="Confirm Password" value="" >

						</div>

						<div class="field field-submit">

							<input type="hidden" name="accounts_action" value="accounts_reset_password">
							<input type="hidden" name="token" value="<?php echo $_GET['key']; ?>">
							<?php wp_nonce_field( 'accounts' ); ?>

							<input type="submit" name="submit" value="Reset Password" class="button-primary">

						</div>

					</form>

				</div>

			</div>

		</div><!-- //End New Account -->

	</div>

</section>

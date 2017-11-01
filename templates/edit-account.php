<?php global $current_user;   ?>

<section id="details" class="accounts-section accounts-section-edit-account row">

	<div class="container">

		<div class="wrap">

			<div class="panel">


				<h4 class="title">My Account Details</h4>

				<div class="text accounts-login">

					<form action="/account" method="post" name="account-edit-form" class="account-edit-form">

						<fieldset>

							<div class="field field-first">

								<label>First Name</label>

								<input type="text" name="account[first_name]" value="<?php echo get_user_meta( $current_user->ID, 'first_name', true); ?>" required>

							</div>

							<div class="field field-last">

								<label>Last Name</label>

								<input type="text" name="account[last_name]" value="<?php echo get_user_meta( $current_user->ID, 'last_name', true); ?>" required>

							</div>

							<div class="field field-email">

								<label>Email</label>

								<input type="email" name="account[email]" value="<?php echo $current_user->data->user_email; ?>" required>

							</div>

						</fieldset>

						<?php do_action('accounts_account_form_before_submit'); ?>

						<div class="field field-submit">

							<input type="hidden" name="accounts_action" value="account_update">
							<?php wp_nonce_field( 'accounts' ); ?>

							<button class="button-primary" name="submit">
								<span class="text">Update</span>
								<span class="icon icon-right-open"></span>
							</button>

						</div>

					</form>


				</div>

			</div>

		</div>

	</div>

</section>

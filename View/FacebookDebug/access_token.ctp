<div class="form">
    <h2>Facebook Auth Token</h2>

    <p class="well" style="word-break: break-all;">
        Current Auth Token:
        <br />
        <?php echo h($this->Session->read('Facebook.Auth.accessToken')); ?>
    </p>
    <?php echo $this->Form->create(null); ?>
    <?php
        echo $this->Form->input('authToken', array('type' => 'textarea'));
        echo $this->Form->submit();
    ?>
    <?php echo $this->Form->end(); ?>

    <?php debug($this->Session->read()); ?>


</div>
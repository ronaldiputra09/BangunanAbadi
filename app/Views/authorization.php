<?= $this->include('Header'); ?>
<?= $this->include('Sidebar'); ?>
<?= $this->include('Navbar'); ?>

<body id="page-top">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="page-title"> Authorization Settings <?= $DataAuth->Username ?></h3>
                        </div>
                        <div class="card-body">
                            <form class="forms-sample" action="<?= base_url('save_auth') ?>" method="post">
                                <div class="form-group">
                                    <div class="input-group col-xs-12">
                                        <input hidden value="<?= $DataAuth->Username ?>" readonly type="text" name="Username" id="Username" class="form-control" required>
                                    </div>
                                </div>
                                <fieldset>
                                    <b><label>General Ledger Modul</label><br></b>
                                    <?php if ($DataAuth->General_Ledger == 1) { ?>
                                        <input type="radio" id="General_Ledger" name="General_Ledger" value="1" checked="">
                                        <label for="html">Diizinkan</label>
                                        <input type="radio" id="General_Ledger" name="General_Ledger" value="0">
                                        <label for="css">Tidak Diizinkan</label>
                                    <?php } else { ?>
                                        <input type="radio" id="General_Ledger" name="General_Ledger" value="1">
                                        <label for="html">Diizinkan</label>
                                        <input type="radio" id="General_Ledger" name="General_Ledger" value="0" checked="">
                                        <label for="css">Tidak Diizinkan</label>
                                    <?php } ?>
                                </fieldset>
                                <br>
                                <fieldset>
                                    <b><label>Purchases Modul</label><br></b>
                                    <?php if ($DataAuth->Purchases == 1) { ?>
                                        <input type="radio" id="Purchases" name="Purchases" value="1" checked="">
                                        <label for="html">Diizinkan</label>
                                        <input type="radio" id="Purchases" name="Purchases" value="0">
                                        <label for="css">Tidak Diizinkan</label>
                                    <?php } else { ?>
                                        <input type="radio" id="Purchases" name="Purchases" value="1">
                                        <label for="html">Diizinkan</label>
                                        <input type="radio" id="Purchases" name="Purchases" value="0" checked="">
                                        <label for="css">Tidak Diizinkan</label>
                                    <?php } ?>
                                </fieldset>
                                <br>
                                <fieldset>
                                    <b> <label>Sales Modul</label><br></b>
                                    <?php if ($DataAuth->Sales == 1) { ?>
                                        <input type="radio" id="Sales" name="Sales" value="1" checked="">
                                        <label for="html">Diizinkan</label>
                                        <input type="radio" id="Sales" name="Sales" value="0">
                                        <label for="css">Tidak Diizinkan</label>
                                    <?php } else { ?>
                                        <input type="radio" id="Sales" name="Sales" value="1">
                                        <label for="html">Diizinkan</label>
                                        <input type="radio" id="Sales" name="Sales" value="0" checked="">
                                        <label for="css">Tidak Diizinkan</label>
                                    <?php } ?>
                                </fieldset>
                                <br>
                                <fieldset>
                                    <b><label>Cash Bank Modul</label><br></b>
                                    <?php if ($DataAuth->CashBank == 1) { ?>
                                        <input type="radio" id="CashBank" name="CashBank" value="1" checked="">
                                        <label for="html">Diizinkan</label>
                                        <input type="radio" id="CashBank" name="CashBank" value="0">
                                        <label for="css">Tidak Diizinkan</label>
                                    <?php } else { ?>
                                        <input type="radio" id="CashBank" name="CashBank" value="1">
                                        <label for="html">Diizinkan</label>
                                        <input type="radio" id="CashBank" name="CashBank" value="0" checked="">
                                        <label for="css">Tidak Diizinkan</label>
                                    <?php } ?>
                                </fieldset>
                                <br>
                                <fieldset>
                                    <b><label>Inventory Modul</label><br></b>
                                    <?php if ($DataAuth->Inventory == 1) { ?>
                                        <input type="radio" id="Inventory" name="Inventory" value="1" checked="">
                                        <label for="html">Diizinkan</label>
                                        <input type="radio" id="Inventory" name="Inventory" value="0">
                                        <label for="css">Tidak Diizinkan</label>
                                    <?php } else { ?>
                                        <input type="radio" id="Inventory" name="Inventory" value="1">
                                        <label for="html">Diizinkan</label>
                                        <input type="radio" id="Inventory" name="Inventory" value="0" checked="">
                                        <label for="css">Tidak Diizinkan</label>
                                    <?php } ?>
                                </fieldset>
                                <br>
                                <fieldset>
                                    <b><label>Users Menu</label><br></b>
                                    <?php if ($DataAuth->User == 1) { ?>
                                        <input type="radio" id="User" name="User" value="1" checked="">
                                        <label for="html">Diizinkan</label>
                                        <input type="radio" id="User" name="User" value="0">
                                        <label for="css">Tidak Diizinkan</label>
                                    <?php } else { ?>
                                        <input type="radio" id="User" name="User" value="1">
                                        <label for="html">Diizinkan</label>
                                        <input type="radio" id="User" name="User" value="0" checked="">
                                        <label for="css">Tidak Diizinkan</label>
                                    <?php } ?>
                                </fieldset>
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <button type="submit" class="btn btn-primary mr-2">Submit</button>
            </form>

</body>




<?= $this->include('Footer'); ?>
<?php
class VO_Account extends Zend_Db_Table_Row {
    
    public function calculateBenefit(){
        $transactions = Proxy_Transaction::getInstance()->retrieveBetweenByAccount($this);
        $income = $expense = 0;
        foreach ($transactions as $transaction){
            if ($transaction->date >= $this->date_ini && $transaction->date <= $this->date_end){
                if ($transaction->id_transaction_type == '1'){
                    $income = $income + $transaction->value;
                }
                else{
                    $expense = $expense + $transaction->value;
                }
            }
        }
        $benefit = $income - $expense;
        return $benefit;
    }
    
    public function delete() {
        $acctras = Proxy_AccTra::getInstance()->retrieveAllByAccountId($this->id);
        foreach ($acctras as $acctra){
            $acctra->delete();
        }
        return parent::delete();
    }
}
?>

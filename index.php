<?php
ini_set('log_errors','on');
ini_set('error_log','php.log');
session_start();

// トレーナーを格納
$trainer = array();
// トレーナーの筋肉クラス
class KINNIKU{
    const BODYBILL = 1;
    const MATTYO = 2;
    const COMMON = 3;
    const GARI = 4;
}
// 抽象クラス（生き物クラス）
abstract class Creature{
    protected $name;
    protected $hp;
    protected $attackMin;
    protected $attackMax;
    abstract public function sayCry();
    public function setName($str){
        $this->name = $str;
    }
    public function getName(){
        return $this->name;
    }
    public function setHp($num){
        $this->hp = $num;
    }
    public function getHp(){
        return $this->hp;
    }
    public function attack($targetObj){
        $attackPoint = mt_rand($this->attackMin, $this->attackMax);
        // 10分の1の確率で火事場の馬鹿力
        if(!mt_rand(0,9)){
            $attackPoint = $attackPoint * 2.0;
            $attackPoint = (int)$attackPoint;
            History::set($this->getName().'の火事場の馬鹿力！！');
        }
        $targetObj->setHp($targetObj->getHp()-$attackPoint);
        History::set($attackPoint.'ポイントのダメージ！');
    }
}
// 人クラス
class Human extends Creature{
    protected $kinniku;
    public function __construct($name,$kinniku,$hp,$attackMin,$attackMax){
        $this->name = $name;
        $this->kinniku = $kinniku;
        $this->hp = $hp;
        $this->attackMin = $attackMin;
        $this->attackMax = $attackMax;
    }
    public function setKinniku($num){
        $this->kinniku = $num;
    }
    public function getKinniku(){
        return $this->kinniku;
    }
    public function sayCry(){
        History::set($this->name.'が声を上げる！！');
        switch($this->kinniku){
            case Kinniku::BODYBILL :
                History::set('え？こんなもん？');
            break;
            case Kinniku::MATTYO :
                History::set('もっと負荷を頂戴！♡');
            break;
            case Kinniku::COMMON :
                History::set('ぐはっ！');
            break;
            case Kinniku::GARI :
                History::set('グァあああ！');
            break;
        }
    }
}
// トレーナークラス
class Trainer extends Creature{
    protected $img;

    public function __construct($name,$hp,$img,$attackMin,$attackMax){
        $this->name = $name;
        $this->hp = $hp;
        $this->img = $img;
        $this->attackMin = $attackMin;
        $this->attackMax = $attackMax;
    }

    public function getImg(){
        return $this->img;
    }
    public function sayCry(){
        History::set($this->name.'が叫ぶ！！');
        History::set('まだまだ追い込めよ！！');
    }
}

class SuperSet extends Trainer{
    private $superset;
    function __construct($name,$hp,$img,$attackMin,$attackMax,$superset){
        parent::__construct($name,$hp,$img,$attackMin,$attackMax);
        $this->superset = $superset;
    }
    public function getsuperset(){
        return $this->superset;
    }
    public function attack($targetObj){
        if(!mt_rand(0,4)){
            History::set($this->name.'のスーパーセット！！');
            $targetObj->setHp($targetObj->getHp() - $this->superset);
            History::set($this->superset.'ポイントのダメージを受けた！');
        }else{
            parent::attack($targetObj);
        }
    }
}
interface HistoryInterface{
    public static function set($str);
    public static function clear();
}
class History implements HistoryInterface{
    public static function set($str){
        if(empty($_SESSION['history'])) $_SESSION['history'] = '';
        $_SESSION['history'].=$str.'<br>';
    }
    public static function clear(){
        unset($_SESSION['history']);
    }
}
// インスタンス生成
$human = new Human('トレーニング初心者',KINNIKU::GARI,100,40,120);
$trainers[] = new Trainer('トレーニング好きのおじちゃん',50,'img/img01.png',20,10);
$trainers[] = new Trainer('イキリの学生',80,'img/img2.png',30,10);
$trainers[] = new SuperSet('ボディビルダーの男',100,'img/img3.png',40,50, mt_rand(80,100));


function createTrainer(){
    global $trainers;
    $trainer = $trainers[mt_rand(0,7)];
    History::set($trainer->getName().'が現れた！');
    $_SESSION['trainer'] = $trainer;
}
function createHuman(){
    global $human;
    $_SESSION['human'] = $human;
}
function init(){
    History::clear();
    History::set('初期化します。');
    $_SESSION['knockDownCount'] = 0;
    createHuman();
    createTrainer();
}
function gameOver(){
    $_SESSION = array();
}

// 1.post送信されていた場合
if(!empty($_POST)){
    $attackFlg = (!empty($_POST['attack'])) ? true  : false;
    $startFlg = (!empty($_POST['start'])) ? true : false;
    error_log('POSTされた！');

    if($startFlg){
        History::set('ゲームスタート！');
        init();
    }else{
        if($attackFlg){
            History::set($_SESSION['human']->getName().'のメニュー！');
            $_SESSION['human']->attack($_SESSION['trainer']);
            $_SESSION['trainer']->sayCry();

            // 自分のhpが０以下になったらゲームオーバー
            if($_SESSION['human']->getHp() <= 0){
                gameOver();
            }else{
                if($_SESSION['trainer']->getHp() <= 0){
                    History::set($_SESSION['trainer']->getName().'のメニューを突破した！');
                    createTrainer();
                    $_SESSION['knockDownCount'] = $_SESSION['knockDownCount']+1;
                }
            }
        }else{
            History::set('逃げた！');
            createTrainer();
        }
    }
    $_POST = array();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>タイトル</title>
</head>
<body>
    <h1>ゲーム「合トレ!!」</h1>
    <div>
        <?php if(empty($_SESSION)){ ?>
            <h2>GAME START ?</h2>
            <form method="post">
            <input type="submit" name="start" value="▶︎ゲームスタート">
            </form>
        <?php }else{ ?>
        <h2><?php echo $_SESSION['trainer']->getName().'が現れた！！';?></h2>
        <div>
            <img src="<?php echo $_SESSION['trainer']->getImg(); ?>" alt="">
        </div>
        <p>残りトレーニングメニュー:<?php echo $_SESSION['trainer']->getHp(); ?></p>
            <p>合トレを行った数:<?php echo $_SESSION['knockDownCount']; ?></p>
            <p>自分の残りエネルギー:<?php echo $_SESSION['human']->getHp(); ?></p>
            <form method="post">
                <input type="submit" name="attack" value="▶︎メニューをこなす">
                <input type="submit" name="drink" value="▶︎ワークアウトドリンクを飲む">
                <input type="submit" name="escape" value="▶︎逃げる">
                <input type="submit" name="start" value="▶︎ゲームリスタート">
            </form>
            <?php } ?>
            <div>
                <p><?php echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : '';?></p>
            </div>
    </div>
</body>
</html>
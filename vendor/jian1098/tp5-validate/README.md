## tp5-validate说明
Thinkphp5.0框架命令行创建验证类validate，Thinkphp5.0版本自带的命令行没有提供`make:validate`命令，而5.1及以上版本是有的，对于使用tp5.0框架或者基于tp5.0的第三框架（如：FastAdmin）非常不友好。你可以使用这个扩展来添加`make:validate`命令生成validate类。扩展的逻辑是从5.1框架源码直接复制过来的，所以验证器用法跟官方的一模一样，请放心使用。

**注意**：本扩展仅适用于Thinkphp5.0版本，更高的版本还是推荐使用自带的命令。



## 如何使用

1.安装扩展

```bash
composer require jian1098/tp5-validate:dev-master
```

2.注册命令

打开`application/command.php`，在数组中增加一行引入即可

```php
return [
    'Jian1098\Tp5Validate\Command\Validate'
];
```

在命令行输入`php think`查看命令行列表，可以看到`make:validate`命令已添加

```
Think Console version 0.1
...
 make
  make:controller    Create a new resource controller class
  make:model         Create a new model class
  make:validate      Create a new validate class
...
```

3.创建验证器

根据5.1版本的文档创建即可

```bash
php think make:validate TestValidate
```

该命令会生成`application/common/validate/TestValidate.php`文件

4.控制器中调用验证器

- 先在上面创建的`TestValidate.php`中填写验证规则和验证场景

  ```php
  <?php
  
  namespace app\common\validate;
  
  use think\Validate;
  
  class TestValidate extends Validate
  {
      /**
           * 定义验证规则
           * 格式：'字段名'	=>	['规则1','规则2'...]
           *
           * @var array
           */
      	protected $rule = [
              'email' => 'require|email',
              'password' => 'require|min:6',
          ];
  
          /**
           * 定义错误信息
           * 格式：'字段名.规则名'	=>	'错误信息'
           *
           * @var array
           */
          protected $message = [
              'email.email' => '邮箱格式不正确',
          ];
  
          /**
           * 验证场景定义
           * 格式：'场景名称'	=>	['字段1','字段2'...]
           *
           * @var array
           */
          protected $scene = [
              'login' => ['email', 'password'],
          ];
  }
  ```

- 在控制器中验证

  ```php
  <?php
  
  namespace app\index\controller;
  
  use think\Controller;
  
  
  class Test extends Controller
  {
      public function index()
      {
          $data = $this->request->param();
          $result = $this->validate($data,'TestValidate.login');
          if(true !== $result){
              // 验证失败 输出错误信息
              dump($result);
              exit();
          }
  
          echo 'success';
      }
  }
  ```

  


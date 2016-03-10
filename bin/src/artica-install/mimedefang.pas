unit mimedefang;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils,IniFiles, Process,logs,unix,RegExpr in 'RegExpr.pas',zsystem;

type LDAP=record
      admin:string;
      password:string;
      suffix:string;
      servername:string;
      Port:string;
  end;

  type
  tmimedefang=class


private
     LOGS:Tlogs;
     SYS:TSystem;
     artica_path:string;
public
    procedure   Free;
    constructor Create(const zSYS:Tsystem);

    function    INITD():string;
    function    SOCKET_PATH():string;

    function    mimedefang_PID():string;
    function    mimedefangmx_PID():string;

    function    BIN_PATH():string;

    function    Graphdefang_path():string;
    function    MULTIPLEXOR_PATH():string;
    function    CONF_PATH():string;

END;

implementation

constructor tmimedefang.Create(const zSYS:Tsystem);
begin
       forcedirectories('/etc/artica-postfix');
       LOGS:=tlogs.Create();
       SYS:=Zsys;


       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure tmimedefang.free();
begin
    logs.Free;
end;
//##############################################################################
function tmimedefang.INITD():string;
begin
   if FileExists('/etc/init.d/mimedefang') then exit('/etc/init.d/mimedefang');
end;
//##############################################################################
function tmimedefang.SOCKET_PATH():string;
begin
    if FileExists('/var/spool/MIMEDefang/mimedefang.sock') then exit('/var/spool/MIMEDefang/mimedefang.sock');
    exit('/var/spool/MIMEDefang/mimedefang.sock');
end;
//##############################################################################
function tmimedefang.mimedefang_PID():string;
begin
if FileExists('/var/spool/MIMEDefang/mimedefang.pid') then exit(SYS.GET_PID_FROM_PATH('/var/spool/MIMEDefang/mimedefang.pid'));
end;
//##############################################################################
function tmimedefang.mimedefangmx_PID():string;
begin
if FileExists('/var/spool/MIMEDefang/mimedefang-multiplexor.pid') then exit(SYS.GET_PID_FROM_PATH('/var/spool/MIMEDefang/mimedefang-multiplexor.pid'));
end;
//##############################################################################
function tmimedefang.Graphdefang_path():string;
begin
if FileExists('/usr/bin/graphdefang.pl') then exit('/usr/bin/graphdefang.pl');
end;
//##############################################################################
function tmimedefang.MULTIPLEXOR_PATH():string;
begin
    if FileExists('/usr/bin/mimedefang-multiplexor') then exit('/usr/bin/mimedefang-multiplexor');
end;
//##############################################################################
function tmimedefang.BIN_PATH():string;
begin
result:=SYS.LOCATE_GENERIC_BIN('mimedefang');
end;
//##############################################################################
function tmimedefang.CONF_PATH():string;
begin
if FileExists('/etc/mail/mimedefang-filter') then exit('/etc/mail/mimedefang-filter');
end;
//##############################################################################
end.

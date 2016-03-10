<?php
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
$unix=new unix();

/*sudo apt-get install libgmp-dev libmpfr-dev libmpc-dev
wget http://www.netgull.com/gcc/releases/gcc-4.9.2/gcc-4.9.2.tar.gz
tar xvf gcc-4.9.2.tar.gz
mkdir gcc-build
cd gcc-build
../gcc-4.9.2/configure --enable-languages=c,c++ --disable-multilib
make
sudo make install


wget http://caml.inria.fr/pub/distrib/ocaml-4.02/ocaml-4.02.1.tar.gz
tar xvf ocaml-4.02.1.tar.gz
cd ocaml-4.02.1
./configure
make world.opt
sudo make install


mkdir dev
cd dev
git clone git://github.com/facebook/hhvm.git --depth=1
export CMAKE_PREFIX_PATH=`pwd`
cd hhvm
git submodule update --init --recursive
cd ..


*/

$binaries["c++"]=true;
$binaries["cpp"]=true;
$binaries["g++"]=true;
$binaries["gcc"]=true;
$binaries["gcc-ar"]=true;
$binaries["gcc-nm"]=true;
$binaries["gcc-ranlib"]=true;
$binaries["gcov"]=true;

$binaries["ocaml"]=true;
$binaries["ocamlbuild"]=true;
$binaries["ocamlbuild.byte"]=true;
$binaries["ocamlbuild.native"]=true;
$binaries["ocamlc"]=true;
$binaries["ocamlc.opt"]=true;
$binaries["ocamlcp"]=true;
$binaries["ocamldebug"]=true;
$binaries["ocamldep"]=true;
$binaries["ocamldep.opt"]=true;
$binaries["ocamldoc"]=true;
$binaries["ocamldoc.opt"]=true;
$binaries["ocamllex"]=true;
$binaries["ocamllex.opt"]=true;
$binaries["ocamlmklib"]=true;
$binaries["ocamlmktop"]=true;
$binaries["ocamlobjinfo"]=true;
$binaries["ocamlopt"]=true;
$binaries["ocamlopt.opt"]=true;
$binaries["ocamloptp"]=true;
$binaries["ocamlprof"]=true;
$binaries["ocamlrun"]=true;
$binaries["ocamlyacc"]=true;
$binaries["x86_64-unknown-linux-gnu-c++"]=true;
$binaries["x86_64-unknown-linux-gnu-g++"]=true;
$binaries["x86_64-unknown-linux-gnu-gcc"]=true;
$binaries["x86_64-unknown-linux-gnu-gcc-4.9.2"]=true;
$binaries["x86_64-unknown-linux-gnu-gcc-ar"]=true;
$binaries["x86_64-unknown-linux-gnu-gcc-nm"]=true;
$binaries["x86_64-unknown-linux-gnu-gcc-ranlib"]=true;


@mkdir("/root/hhvm/usr/local/bin");
@mkdir("/root/hhvm/usr/local/lib/gcc");
@mkdir("/root/hhvm/usr/local/lib/ocaml");
@mkdir("/root/hhvm/usr/local/libexec/gcc");
@mkdir("/root/hhvm/usr/local/lib64");

while (list ($filename, $ligne) = each ($binaries) ){
	echo "Installing $filename\n";
	shell_exec("/bin/cp -fd /usr/local/bin/$filename /root/hhvm/usr/local/bin/");

}

$LIB64["libasan.a"]=true;
$LIB64["libatomic.la"]=true;
$LIB64["libcilkrts.so.5"]=true;
$LIB64["libgomp.so"]=true;
$LIB64["libitm.so.1"]=true;
$LIB64["liblsan.so.0.0.0"]=true;
$LIB64["libssp.a "]=true;
$LIB64["libstdc++.a"]=true;
$LIB64["libsupc++.la"]=true;
$LIB64["libubsan.la"]=true;
$LIB64["libvtv.so.0"]=true;
$LIB64["libasan.la"]=true;
$LIB64["libatomic.so"]=true;
$LIB64["libcilkrts.so.5.0.0"]=true;
$LIB64["libgomp.so.1"]=true;
$LIB64["libitm.so.1.0.0"]=true;
$LIB64["libquadmath.a"]=true;
$LIB64["libssp.la "]=true;
$LIB64["libstdc++.la"]=true;
$LIB64["libtsan.a"]=true;
$LIB64["libubsan.so"]=true;
$LIB64["libvtv.so.0.0.0"]=true;
$LIB64["libasan_preinit.o"]=true;
$LIB64["libatomic.so.1"]=true;
$LIB64["libcilkrts.spec"]=true;
$LIB64["libgomp.so.1.0.0"]=true;
$LIB64["libitm.spec"]=true;
$LIB64["libquadmath.la"]=true;
$LIB64["libssp_nonshared.a"]=true;
$LIB64["libstdc++.so"]=true;
$LIB64["libtsan.la"]=true;
$LIB64["libubsan.so.0"]=true;
$LIB64["libasan.so"]=true;
$LIB64["libatomic.so.1.1.0"]=true;
$LIB64["libgcc_s.so"]=true;
$LIB64["libgomp.spec"]=true;
$LIB64["liblsan.a"]=true;
$LIB64["libquadmath.so"]=true;
$LIB64["libssp_nonshared.la"]=true;
$LIB64["libstdc++.so.6"]=true;
$LIB64["libtsan.so"]=true;
$LIB64["libubsan.so.0.0.0"]=true;
$LIB64["libasan.so.1"]=true;
$LIB64["libcilkrts.a"]=true;
$LIB64["libgcc_s.so.1"]=true;
$LIB64["libitm.a"]=true;
$LIB64["liblsan.la"]=true;
$LIB64["libquadmath.so.0"]=true;
$LIB64["libssp.so "]=true;
$LIB64["libstdc++.so.6.0.20"]=true;
$LIB64["libtsan.so.0"]=true;
$LIB64["libvtv.a"]=true;
$LIB64["libasan.so.1.0.0"]=true;
$LIB64["libcilkrts.la"]=true;
$LIB64["libgomp.a"]=true;
$LIB64["libitm.la"]=true;
$LIB64["liblsan.so"]=true;
$LIB64["libquadmath.so.0.0.0"]=true;
$LIB64["libssp.so.0	"]=true;
$LIB64["libstdc++.so.6.0.20-gdb.py"]=true;
$LIB64["libtsan.so.0.0.0"]=true;
$LIB64["libvtv.la"]=true;
$LIB64["libatomic.a"]=true;
$LIB64["libcilkrts.so"]=true;
$LIB64["libgomp.la"]=true;
$LIB64["libitm.so"]=true;
$LIB64["liblsan.so.0"]=true;
$LIB64["libsanitizer.spec"]=true;
$LIB64["libssp.so.0.0.0	"]=true;
$LIB64["libsupc++.a	"]=true;
$LIB64["libubsan.a"]=true;
$LIB64["libvtv.so"]=true;

while (list ($filename, $ligne) = each ($LIB64) ){
	echo "Installing $filename\n";
	shell_exec("/bin/cp -fd /usr/local/lib64/$filename /root/hhvm/usr/local/lib64/");

}


echo "Installing /usr/local/lib/gcc\n";
shell_exec("/bin/cp -rfd /usr/local/lib/gcc/* /root/hhvm/usr/local/lib/gcc/");
echo "Installing /usr/local/libexec/gcc\n";
shell_exec("/bin/cp -rfd /usr/local/libexec/gcc/* /root/hhvm/usr/local/libexec/gcc/");
echo "Installing /usr/local/lib/ocaml\n";
shell_exec("/bin/cp -rfd /usr/local/lib/ocaml/* /root/hhvm/usr/local/lib/ocaml/");
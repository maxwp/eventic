<?php
class Cmd_SuperVisor implements EE_Content_Interface {

    public function main(EE_Request_Interface $request) {
        SuperVisor::Get()->register(
            $request->getArgument('id'),
            $request->getArgument('cn'), // class name
            $request->getArgument('aa'), // argument array
            $request->getArgument('ca'), // config array
            $request->getArgument('ttl')
        );
    }

}
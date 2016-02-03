<?php

namespace Olive;

interface DatabaseInterface {
	
	/*
		Verify if the adapter is supported by the environment
		
		Return
			boolean
	*/
	static public function isSupported();
	
}
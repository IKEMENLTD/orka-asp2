(function(){

	function getThisPageDomain( $iHost )
	{
		$regex1 = new RegExp( /^\w+\.([^\.]+\.\w+\.\w{2})$/ ); //サブドメイン＋国別ドメイン
		$regex2 = new RegExp( /^([^\.]+\.\w+\.\w{2})$/ );      //国別ドメイン
		$regex3 = new RegExp( /^\w+\.([^\.]+\.\w+)$/ );        //サブドメイン
		$regex4 = new RegExp( /^([^\.]+\.\w+)$/ );             //ドメイン
		$result = null;

		if( $regex1.test( $iHost ) )
			{ $result = $regex1.exec( $iHost ); }
		else if( $regex2.test( $iHost ) )
			{ $result = $regex2.exec( $iHost ); }
		else if( $regex3.test( $iHost ) )
			{ $result = $regex3.exec( $iHost ); }
		else if( $regex4.test( $iHost ) )
			{ $result = $regex4.exec( $iHost ); }

		if( null == $result )
			{ return location.hostname; }
		else
			{ return $result[ 1 ]; }
	}

	var $query  = location.search.substr( 1 );
	var $params = {};

	$query.split( '&' ).forEach( function( $item ){
		var $set   = $item.split( '=' );
		var $key   = decodeURIComponent( $set[ 0 ] );
		var $value = decodeURIComponent( $set[ 1 ] );

		if( $key in $params )
			{ $params[ $key ].push( $value ); }
		else
			{ $params[ $key ] = [ $value ]; }
	} );

	if( $params[ 'aid' ] && $params[ 'aid' ][ 0 ] )
		{ document.cookie = 'afl_tracking_aid=' + encodeURIComponent( $params[ 'aid' ][ 0 ] ) + '; domain=' + getThisPageDomain( location.hostname ) + '; path=/; max-age=2592000;'; }
})();

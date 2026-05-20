import * as React from 'react';
const SvgIconAwardCertificate = ( props ) => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="1em"
		height="1em"
		fill="none"
		viewBox="0 0 64 64"
		{ ...props }
	>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 54H2V2h46v14"
		/>
		<path
			stroke="currentColor"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M56 41.486V60l-8-4-8 4V41.486"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M48 44c7.732 0 14-6.268 14-14s-6.268-14-14-14-14 6.268-14 14 6.268 14 14 14ZM12 18h16M12 28h12M12 38h12"
		/>
	</svg>
);
export default SvgIconAwardCertificate;

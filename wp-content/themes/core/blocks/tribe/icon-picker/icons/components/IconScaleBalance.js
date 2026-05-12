import * as React from 'react';
const SvgIconScaleBalance = ( props ) => (
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
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="m14 12 12 18c0 4.218-4 10-12 10S2 34.218 2 30zm0 0h36m0 0 12 18c0 4.218-4 10-12 10s-12-5.782-12-10z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 6v46M16 58h32"
		/>
		<path
			stroke="currentColor"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M2 30h24M38 30h24"
		/>
	</svg>
);
export default SvgIconScaleBalance;

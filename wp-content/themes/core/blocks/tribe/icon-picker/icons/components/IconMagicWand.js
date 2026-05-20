import * as React from 'react';
const SvgIconMagicWand = ( props ) => (
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
			d="m4 52 8 8 36-36-8-8z"
		/>
		<path
			stroke="currentColor"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="m32 24 8 8"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M42 2v5M56.142 7.858l-3.536 3.536M62 22h-5M56.142 36.142l-3.536-3.536M27.858 7.858l3.536 3.536"
		/>
	</svg>
);
export default SvgIconMagicWand;
